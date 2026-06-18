<?php
/**
 * TERRA — Chat Main Portal (WhatsApp Style)
 */
require_once __DIR__ . '/../../config.php';
requireLogin();

$user = getCurrentUser();

$page_title = 'Chat Room';
$page_desc = 'Komunitas & Pesan Langsung — TERRA';
$extra_css = '
    <style>
        .chat-container-inner {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            margin: 20px auto;
            max-width: 1100px;
            overflow: hidden;
        }
        
        .chat-empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-tertiary);
            padding: var(--space-xl);
            text-align: center;
            background: var(--bg-primary);
        }
        
        @media (max-width: 768px) {
            .chat-container-inner {
                margin: 0;
                border-radius: 0;
                border: none;
            }
        }
    </style>
';

$active_page = 'chat';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container" style="padding: 0;">
    <div class="chat-container-inner">
        <!-- Index Page: No active-room class, so it displays the sidebar list on mobile -->
        <div id="chatContainer" class="chat-page-container two-columns">
            
            <!-- Sidebar Component -->
            <?php 
            $current_room_type = 'none';
            $current_room_id = 'none';
            include __DIR__ . '/sidebar.php'; 
            ?>

            <!-- Chat Area (Hidden on mobile by default styles) -->
            <div class="chat-area-middle" id="chatArea" style="background: var(--bg-primary);">
                <div class="chat-empty-state" id="chatEmptyState">
                    <div style="font-size: 54px; margin-bottom: 16px;">💬</div>
                    <h3 style="font-size: 15px; font-weight: 850; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em;">TERRA Obrolan</h3>
                    <p style="font-size: 12px; max-width: 340px; margin-top: 6px; color: var(--text-secondary); line-height: 1.5;">Pilih saluran global, grup koordinasi, atau kirim pesan pribadi ke teman pendaki untuk memulai diskusi.</p>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
