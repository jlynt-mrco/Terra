<?php
/**
 * TERRA — Chat API Handler
 */
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$user = getCurrentUser();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper to update current user's activity status
function updateActivity($userId) {
    $users = readJSON(USERS_FILE);
    $updated = false;
    foreach ($users as &$u) {
        if ($u['id'] === $userId) {
            $u['last_activity'] = date('c'); // ISO 8601 format
            $updated = true;
            break;
        }
    }
    if ($updated) {
        writeJSON(USERS_FILE, $users);
    }
}

// Update activity timestamp on every API call
updateActivity($user['id']);

// Helper to check if a user is online
function isUserOnline($lastActivity) {
    if (empty($lastActivity)) return false;
    $time = strtotime($lastActivity);
    return (time() - $time) < 15; // Online if active in last 15 seconds
}

switch ($action) {
    // ------------------------------------------------------------
    // GLOBAL CHAT ACTIONS
    // ------------------------------------------------------------
    case 'get_global_messages':
        $messages = readJSON(CHAT_MESSAGES_FILE);
        // Filter only global messages
        $globalMessages = array_filter($messages, function($m) {
            return ($m['type'] ?? '') === 'global';
        });
        
        // Take last 100 messages
        $globalMessages = array_slice(array_values($globalMessages), -100);
        
        // Add online status of senders
        $users = readJSON(USERS_FILE);
        $userStatusMap = [];
        foreach ($users as $u) {
            $userStatusMap[$u['id']] = isUserOnline($u['last_activity'] ?? '');
        }
        
        foreach ($globalMessages as &$m) {
            $m['sender_online'] = $userStatusMap[$m['sender_id']] ?? false;
        }
        
        jsonResponse($globalMessages);
        break;

    case 'send_global_message':
        $messageText = trim($_POST['message'] ?? '');
        if (empty($messageText)) {
            jsonResponse(['error' => 'Message text required'], 400);
        }

        $messages = readJSON(CHAT_MESSAGES_FILE);
        $newMessage = [
            'id' => generateId('msg'),
            'type' => 'global',
            'target_id' => 'global',
            'sender_id' => $user['id'],
            'sender_name' => $user['name'],
            'message' => $messageText,
            'timestamp' => date('c')
        ];
        
        $messages[] = $newMessage;
        writeJSON(CHAT_MESSAGES_FILE, $messages);
        
        jsonResponse($newMessage);
        break;

    // ------------------------------------------------------------
    // GROUP CHAT ACTIONS
    // ------------------------------------------------------------
    case 'get_groups':
        $groups = readJSON(CHAT_GROUPS_FILE);
        $memberships = readJSON(GROUP_MEMBERS_FILE);
        
        $userGroups = [];
        foreach ($groups as $g) {
            // Check if current user is member
            $isMember = false;
            $memberCount = 0;
            foreach ($memberships as $m) {
                if ($m['group_id'] === $g['id']) {
                    $memberCount++;
                    if ($m['user_id'] === $user['id']) {
                        $isMember = true;
                    }
                }
            }
            $g['is_member'] = $isMember;
            $g['member_count'] = $memberCount;
            $userGroups[] = $g;
        }
        
        jsonResponse($userGroups);
        break;

    case 'create_group':
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            jsonResponse(['error' => 'Group name required'], 400);
        }
        
        $groups = readJSON(CHAT_GROUPS_FILE);
        $newGroup = [
            'id' => generateId('group'),
            'name' => $name,
            'description' => $description,
            'created_by' => $user['id'],
            'created_at' => date('c')
        ];
        
        $groups[] = $newGroup;
        writeJSON(CHAT_GROUPS_FILE, $groups);
        
        // Automatically join group creator
        $memberships = readJSON(GROUP_MEMBERS_FILE);
        $memberships[] = [
            'group_id' => $newGroup['id'],
            'user_id' => $user['id'],
            'joined_at' => date('c')
        ];
        writeJSON(GROUP_MEMBERS_FILE, $memberships);
        
        jsonResponse($newGroup);
        break;

    case 'join_group':
        $groupId = $_POST['group_id'] ?? '';
        if (empty($groupId)) {
            jsonResponse(['error' => 'Group ID required'], 400);
        }
        
        $memberships = readJSON(GROUP_MEMBERS_FILE);
        $alreadyJoined = false;
        foreach ($memberships as $m) {
            if ($m['group_id'] === $groupId && $m['user_id'] === $user['id']) {
                $alreadyJoined = true;
                break;
            }
        }
        
        if (!$alreadyJoined) {
            $memberships[] = [
                'group_id' => $groupId,
                'user_id' => $user['id'],
                'joined_at' => date('c')
            ];
            writeJSON(GROUP_MEMBERS_FILE, $memberships);
        }
        
        jsonResponse(['success' => true]);
        break;

    case 'leave_group':
        $groupId = $_POST['group_id'] ?? '';
        if (empty($groupId)) {
            jsonResponse(['error' => 'Group ID required'], 400);
        }
        
        $memberships = readJSON(GROUP_MEMBERS_FILE);
        $filtered = array_filter($memberships, function($m) use ($groupId, $user) {
            return !($m['group_id'] === $groupId && $m['user_id'] === $user['id']);
        });
        writeJSON(GROUP_MEMBERS_FILE, array_values($filtered));
        
        jsonResponse(['success' => true]);
        break;

    case 'get_group_messages':
        $groupId = $_GET['group_id'] ?? '';
        if (empty($groupId)) {
            jsonResponse(['error' => 'Group ID required'], 400);
        }
        
        // First verify membership
        $memberships = readJSON(GROUP_MEMBERS_FILE);
        $isMember = false;
        foreach ($memberships as $m) {
            if ($m['group_id'] === $groupId && $m['user_id'] === $user['id']) {
                $isMember = true;
                break;
            }
        }
        
        if (!$isMember) {
            jsonResponse(['error' => 'Not a member of this group'], 403);
        }
        
        $messages = readJSON(CHAT_MESSAGES_FILE);
        $groupMessages = array_filter($messages, function($m) use ($groupId) {
            return ($m['type'] ?? '') === 'group' && ($m['target_id'] ?? '') === $groupId;
        });
        
        // Slice last 100 messages
        $groupMessages = array_slice(array_values($groupMessages), -100);
        
        // Add online status
        $users = readJSON(USERS_FILE);
        $userStatusMap = [];
        foreach ($users as $u) {
            $userStatusMap[$u['id']] = isUserOnline($u['last_activity'] ?? '');
        }
        foreach ($groupMessages as &$m) {
            $m['sender_online'] = $userStatusMap[$m['sender_id']] ?? false;
        }
        
        jsonResponse($groupMessages);
        break;

    case 'send_group_message':
        $groupId = $_POST['group_id'] ?? '';
        $messageText = trim($_POST['message'] ?? '');
        
        if (empty($groupId) || empty($messageText)) {
            jsonResponse(['error' => 'Group ID and message text required'], 400);
        }
        
        // Verify membership
        $memberships = readJSON(GROUP_MEMBERS_FILE);
        $isMember = false;
        foreach ($memberships as $m) {
            if ($m['group_id'] === $groupId && $m['user_id'] === $user['id']) {
                $isMember = true;
                break;
            }
        }
        
        if (!$isMember) {
            jsonResponse(['error' => 'Not a member of this group'], 403);
        }
        
        $messages = readJSON(CHAT_MESSAGES_FILE);
        $newMessage = [
            'id' => generateId('msg'),
            'type' => 'group',
            'target_id' => $groupId,
            'sender_id' => $user['id'],
            'sender_name' => $user['name'],
            'message' => $messageText,
            'timestamp' => date('c')
        ];
        
        $messages[] = $newMessage;
        writeJSON(CHAT_MESSAGES_FILE, $messages);
        
        jsonResponse($newMessage);
        break;

    case 'get_group_details':
        $groupId = $_GET['group_id'] ?? '';
        if (empty($groupId)) {
            jsonResponse(['error' => 'Group ID required'], 400);
        }
        
        $groups = readJSON(CHAT_GROUPS_FILE);
        $targetGroup = null;
        foreach ($groups as $g) {
            if ($g['id'] === $groupId) {
                $targetGroup = $g;
                break;
            }
        }
        
        if (!$targetGroup) {
            jsonResponse(['error' => 'Group not found'], 404);
        }
        
        $memberships = readJSON(GROUP_MEMBERS_FILE);
        $users = readJSON(USERS_FILE);
        
        $groupUserIds = [];
        foreach ($memberships as $m) {
            if ($m['group_id'] === $groupId) {
                $groupUserIds[] = $m['user_id'];
            }
        }
        
        $membersList = [];
        foreach ($users as $u) {
            if (in_array($u['id'], $groupUserIds)) {
                $membersList[] = [
                    'id' => $u['id'],
                    'name' => $u['name'],
                    'email' => $u['email'],
                    'online' => isUserOnline($u['last_activity'] ?? '')
                ];
            }
        }
        
        $targetGroup['members'] = $membersList;
        jsonResponse($targetGroup);
        break;

    // ------------------------------------------------------------
    // DIRECT MESSAGING ACTIONS
    // ------------------------------------------------------------
    case 'search_users':
        $query = trim($_GET['query'] ?? '');
        if (strlen($query) < 2) {
            jsonResponse([]);
        }
        
        $users = readJSON(USERS_FILE);
        $results = [];
        foreach ($users as $u) {
            // Exclude current user
            if ($u['id'] === $user['id']) continue;
            
            if (stripos($u['name'], $query) !== false || stripos($u['email'], $query) !== false) {
                $results[] = [
                    'id' => $u['id'],
                    'name' => $u['name'],
                    'email' => $u['email'],
                    'online' => isUserOnline($u['last_activity'] ?? '')
                ];
            }
        }
        
        jsonResponse($results);
        break;

    case 'start_dm':
        $targetUserId = $_POST['target_user_id'] ?? '';
        if (empty($targetUserId)) {
            jsonResponse(['error' => 'Target user ID required'], 400);
        }
        
        $rooms = readJSON(DM_ROOMS_FILE);
        $existingRoom = null;
        
        foreach ($rooms as $r) {
            if (($r['user1_id'] === $user['id'] && $r['user2_id'] === $targetUserId) || 
                ($r['user1_id'] === $targetUserId && $r['user2_id'] === $user['id'])) {
                $existingRoom = $r;
                break;
            }
        }
        
        if ($existingRoom) {
            jsonResponse($existingRoom);
        } else {
            $newRoom = [
                'id' => generateId('room'),
                'user1_id' => $user['id'],
                'user2_id' => $targetUserId,
                'created_at' => date('c')
            ];
            $rooms[] = $newRoom;
            writeJSON(DM_ROOMS_FILE, $rooms);
            jsonResponse($newRoom);
        }
        break;

    case 'get_dm_rooms':
        $rooms = readJSON(DM_ROOMS_FILE);
        $messages = readJSON(DM_MESSAGES_FILE);
        $users = readJSON(USERS_FILE);
        
        // Index users by ID
        $userMap = [];
        foreach ($users as $u) {
            $userMap[$u['id']] = $u;
        }
        
        $myRooms = [];
        foreach ($rooms as $r) {
            if ($r['user1_id'] === $user['id'] || $r['user2_id'] === $user['id']) {
                $otherUserId = ($r['user1_id'] === $user['id']) ? $r['user2_id'] : $r['user1_id'];
                $otherUser = $userMap[$otherUserId] ?? null;
                
                if (!$otherUser) continue;
                
                // Fetch last message & unread count
                $lastMsg = null;
                $unreadCount = 0;
                foreach ($messages as $m) {
                    if ($m['room_id'] === $r['id']) {
                        $lastMsg = $m;
                        if ($m['sender_id'] !== $user['id'] && !($m['is_read'] ?? false)) {
                            $unreadCount++;
                        }
                    }
                }
                
                $myRooms[] = [
                    'id' => $r['id'],
                    'other_user' => [
                        'id' => $otherUser['id'],
                        'name' => $otherUser['name'],
                        'email' => $otherUser['email'],
                        'online' => isUserOnline($otherUser['last_activity'] ?? '')
                    ],
                    'last_message' => $lastMsg ? $lastMsg['message'] : '',
                    'last_message_time' => $lastMsg ? $lastMsg['timestamp'] : '',
                    'unread_count' => $unreadCount
                ];
            }
        }
        
        // Sort rooms by last message time desc
        usort($myRooms, function($a, $b) {
            return strcmp($b['last_message_time'], $a['last_message_time']);
        });
        
        jsonResponse($myRooms);
        break;

    case 'get_dm_messages':
        $roomId = $_GET['room_id'] ?? '';
        if (empty($roomId)) {
            jsonResponse(['error' => 'Room ID required'], 400);
        }
        
        $rooms = readJSON(DM_ROOMS_FILE);
        $authorized = false;
        $otherUserId = '';
        foreach ($rooms as $r) {
            if ($r['id'] === $roomId) {
                if ($r['user1_id'] === $user['id'] || $r['user2_id'] === $user['id']) {
                    $authorized = true;
                    $otherUserId = ($r['user1_id'] === $user['id']) ? $r['user2_id'] : $r['user1_id'];
                    break;
                }
            }
        }
        
        if (!$authorized) {
            jsonResponse(['error' => 'Unauthorized room access'], 403);
        }
        
        $messages = readJSON(DM_MESSAGES_FILE);
        $roomMessages = [];
        $unreadUpdated = false;
        
        foreach ($messages as &$m) {
            if ($m['room_id'] === $roomId) {
                // Mark as read if sender is other user
                if ($m['sender_id'] !== $user['id'] && !($m['is_read'] ?? false)) {
                    $m['is_read'] = true;
                    $unreadUpdated = true;
                }
                $roomMessages[] = $m;
            }
        }
        
        if ($unreadUpdated) {
            writeJSON(DM_MESSAGES_FILE, $messages);
        }
        
        // Get other user's info
        $users = readJSON(USERS_FILE);
        $otherUserInfo = null;
        foreach ($users as $u) {
            if ($u['id'] === $otherUserId) {
                $otherUserInfo = [
                    'id' => $u['id'],
                    'name' => $u['name'],
                    'email' => $u['email'],
                    'phone' => $u['phone'] ?? '',
                    'online' => isUserOnline($u['last_activity'] ?? '')
                ];
                break;
            }
        }
        
        jsonResponse([
            'messages' => $roomMessages,
            'other_user' => $otherUserInfo
        ]);
        break;

    case 'send_dm_message':
        $roomId = $_POST['room_id'] ?? '';
        $messageText = trim($_POST['message'] ?? '');
        
        if (empty($roomId) || empty($messageText)) {
            jsonResponse(['error' => 'Room ID and message text required'], 400);
        }
        
        // Verify room authorization
        $rooms = readJSON(DM_ROOMS_FILE);
        $authorized = false;
        foreach ($rooms as $r) {
            if ($r['id'] === $roomId) {
                if ($r['user1_id'] === $user['id'] || $r['user2_id'] === $user['id']) {
                    $authorized = true;
                    break;
                }
            }
        }
        
        if (!$authorized) {
            jsonResponse(['error' => 'Unauthorized room access'], 403);
        }
        
        $messages = readJSON(DM_MESSAGES_FILE);
        $newMsg = [
            'id' => generateId('dmsg'),
            'room_id' => $roomId,
            'sender_id' => $user['id'],
            'message' => $messageText,
            'timestamp' => date('c'),
            'is_read' => false
        ];
        
        $messages[] = $newMsg;
        writeJSON(DM_MESSAGES_FILE, $messages);
        
        jsonResponse($newMsg);
        break;

    // ------------------------------------------------------------
    // STATUS & NOTIFICATIONS GLOBAL POLLING ACTIONS
    // ------------------------------------------------------------
    case 'get_status':
        $rooms = readJSON(DM_ROOMS_FILE);
        $dmMessages = readJSON(DM_MESSAGES_FILE);
        
        // Calculate total unread DMs
        $totalUnread = 0;
        foreach ($rooms as $r) {
            if ($r['user1_id'] === $user['id'] || $r['user2_id'] === $user['id']) {
                foreach ($dmMessages as $m) {
                    if ($m['room_id'] === $r['id'] && $m['sender_id'] !== $user['id'] && !($m['is_read'] ?? false)) {
                        $totalUnread++;
                    }
                }
            }
        }
        
        jsonResponse([
            'unread_dm_count' => $totalUnread
        ]);
        break;

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
