<?php
/**
 * TERRA — Data API
 */
require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'mountains':
        $mountains = readJSON(MOUNTAINS_FILE);
        jsonResponse($mountains);
        break;

    case 'mountain':
        $id = $_GET['id'] ?? '';
        $mountain = getMountain($id);
        if ($mountain) {
            jsonResponse($mountain);
        } else {
            jsonResponse(['error' => 'Not found'], 404);
        }
        break;

    case 'weather':
        $id = $_GET['id'] ?? '';
        $date = $_GET['date'] ?? null;
        $weather = getSimulatedWeather($id, $date);
        if ($weather) {
            jsonResponse($weather);
        } else {
            jsonResponse(['error' => 'Not found'], 404);
        }
        break;

    case 'forecast':
        $id = $_GET['id'] ?? '';
        $days = intval($_GET['days'] ?? 5);
        $forecast = getWeatherForecast($id, min($days, 14));
        jsonResponse($forecast);
        break;

    case 'quota':
        $id = $_GET['id'] ?? '';
        $date = $_GET['date'] ?? date('Y-m-d');
        jsonResponse([
            'mountain_id' => $id,
            'date' => $date,
            'quota_remaining' => getMountainQuota($id, $date),
            'active_climbers' => getActiveClimbers($id),
            'density' => getDensityLevel($id)
        ]);
        break;

    case 'schedule':
        $id = $_GET['id'] ?? '';
        $days = intval($_GET['days'] ?? 14);
        jsonResponse(getAvailableDates($id, min($days, 30)));
        break;

    default:
        jsonResponse(['error' => 'Invalid action', 'available_actions' => ['mountains', 'mountain', 'weather', 'forecast', 'quota', 'schedule']]);
}
