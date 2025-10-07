# Backup API Quick Reference

## REST API Endpoints

### List All Backups
```bash
GET /wp-json/mas-v2/v1/backups
```

**Parameters:**
- `limit` (optional): Maximum number of backups to return (default: 0 = all)
- `offset` (optional): Offset for pagination (default: 0)

**Example:**
```bash
curl -X GET "http://example.com/wp-json/mas-v2/v1/backups?limit=10&offset=0" \
  -H "X-WP-Nonce: YOUR_NONCE"
```

**Response:**
```json
{
  "success": true,
  "message": "Backups retrieved successfully",
  "data": [
    {
      "id": "1234567890_abc123",
      "timestamp": 1234567890,
      "date": "2025-01-10 15:30:00",
      "type": "manual",
      "metadata": {
        "plugin_version": "2.2.0",
        "wordpress_version": "6.8",
        "user_id": 1,
        "note": "Before theme change"
      }
    }
  ]
}
```

### Get Specific Backup
```bash
GET /wp-json/mas-v2/v1/backups/{id}
```

**Example:**
```bash
curl -X GET "http://example.com/wp-json/mas-v2/v1/backups/1234567890_abc123" \
  -H "X-WP-Nonce: YOUR_NONCE"
```

### Create Backup
```bash
POST /wp-json/mas-v2/v1/backups
```

**Body:**
```json
{
  "note": "Optional note about the backup"
}
```

**Example:**
```bash
curl -X POST "http://example.com/wp-json/mas-v2/v1/backups" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  -d '{"note": "Before major changes"}'
```

### Restore Backup
```bash
POST /wp-json/mas-v2/v1/backups/{id}/restore
```

**Example:**
```bash
curl -X POST "http://example.com/wp-json/mas-v2/v1/backups/1234567890_abc123/restore" \
  -H "X-WP-Nonce: YOUR_NONCE"
```

**Note:** This will:
1. Create an automatic backup of current settings
2. Restore the specified backup
3. Roll back if restore fails

### Delete Backup
```bash
DELETE /wp-json/mas-v2/v1/backups/{id}
```

**Example:**
```bash
curl -X DELETE "http://example.com/wp-json/mas-v2/v1/backups/1234567890_abc123" \
  -H "X-WP-Nonce: YOUR_NONCE"
```

### Get Backup Statistics
```bash
GET /wp-json/mas-v2/v1/backups/statistics
```

**Example:**
```bash
curl -X GET "http://example.com/wp-json/mas-v2/v1/backups/statistics" \
  -H "X-WP-Nonce: YOUR_NONCE"
```

**Response:**
```json
{
  "success": true,
  "message": "Statistics retrieved successfully",
  "data": {
    "total_backups": 15,
    "automatic_backups": 8,
    "manual_backups": 7,
    "total_size_bytes": 524288,
    "total_size_formatted": "512 KB",
    "oldest_backup": 1234567890,
    "newest_backup": 1234567900
  }
}
```

## JavaScript Client Usage

### Basic Usage

```javascript
// Using the global REST client
const client = window.masRestClient;

// List all backups
const backups = await client.listBackups();
console.log('Backups:', backups);

// Create a backup
const newBackup = await client.createBackup({ note: 'Before changes' });
console.log('Created backup:', newBackup);

// Restore a backup
await client.restoreBackup('1234567890_abc123');

// Delete a backup
await client.deleteBackup('1234567890_abc123');
```

### Using BackupManager (Recommended)

```javascript
// Using the global backup manager
const manager = window.masBackupManager;

// List backups with caching
const backups = await manager.listBackups();

// Create backup with progress indicator
await manager.createBackup('Before theme change');

// Restore backup with confirmation dialog
await manager.restoreBackup('1234567890_abc123');
// User will see: "Are you sure you want to restore this backup?"

// Delete backup with confirmation
await manager.deleteBackup('1234567890_abc123');
// User will see: "Are you sure you want to delete this backup?"

// Get formatted backup date
const formattedDate = manager.formatBackupDate(1234567890);
console.log(formattedDate); // "2 hours ago" or "Jan 10, 2025 3:30 PM"

// Get statistics
const stats = manager.getStatistics();
console.log('Total backups:', stats.total);

// Export backup as JSON file
await manager.exportBackup('1234567890_abc123');
```

### Custom BackupManager Instance

```javascript
// Create custom instance with options
const customManager = new BackupManager(masRestClient, {
  confirmRestore: true,      // Show confirmation for restore (default: true)
  confirmDelete: true,        // Show confirmation for delete (default: true)
  showProgress: true,         // Show progress indicators (default: true)
  autoRefresh: true          // Auto-refresh list after operations (default: true)
});

// Use custom instance
await customManager.createBackup('My backup');
```

### Event Listeners

```javascript
// Listen for progress events
window.addEventListener('mas:backup:progress', (event) => {
  const { message, show } = event.detail;
  if (show) {
    console.log('Progress:', message);
    // Show your custom progress indicator
  } else {
    // Hide your custom progress indicator
  }
});

// Listen for loading state changes
window.addEventListener('mas:backup:loading', (event) => {
  const { loading } = event.detail;
  console.log('Loading:', loading);
  // Update UI loading state
});

// Listen for notifications
window.addEventListener('mas:backup:notification', (event) => {
  const { message, type } = event.detail;
  console.log(`${type.toUpperCase()}: ${message}`);
  // Show your custom notification
});
```

## PHP Service Usage

### Basic Usage

```php
// Get service instance
$backup_service = MAS_Backup_Service::get_instance();

// List all backups
$backups = $backup_service->list_backups();

// List with pagination
$backups = $backup_service->list_backups(10, 0); // 10 backups, offset 0

// Get specific backup
$backup = $backup_service->get_backup('1234567890_abc123');

// Create manual backup
$backup = $backup_service->create_backup(null, 'manual', 'My backup note');

// Create automatic backup
$backup = $backup_service->create_automatic_backup('Before settings change');

// Restore backup
$result = $backup_service->restore_backup('1234567890_abc123');

// Delete backup
$result = $backup_service->delete_backup('1234567890_abc123');

// Get statistics
$stats = $backup_service->get_statistics();

// Cleanup old backups
$deleted_count = $backup_service->cleanup_old_backups();
```

### Error Handling

```php
// Check for errors
$result = $backup_service->restore_backup('invalid_id');

if (is_wp_error($result)) {
    $error_code = $result->get_error_code();
    $error_message = $result->get_error_message();
    $error_data = $result->get_error_data();
    
    echo "Error: $error_message (Code: $error_code)";
}
```

### Integration with Settings Service

```php
// The settings service automatically creates backups
$settings_service = MAS_Settings_Service::get_instance();

// This will create an automatic backup before resetting
$settings_service->reset_settings();
```

## Backup Configuration

### Retention Policy

Default settings (can be modified in `class-mas-backup-service.php`):

```php
private $max_automatic_backups = 10;  // Keep max 10 automatic backups
private $max_manual_backups = 20;     // Keep max 20 manual backups
private $retention_days = 30;         // Delete automatic backups older than 30 days
```

### Backup Types

- **Manual**: Created by user action, kept longer
- **Automatic**: Created before major changes, cleaned up more aggressively

## Common Use Cases

### 1. Create Backup Before Changes

```javascript
// JavaScript
await masBackupManager.createBackup('Before theme customization');

// Then make your changes
await masRestClient.saveSettings(newSettings);
```

### 2. Restore Previous State

```javascript
// List backups
const backups = await masBackupManager.listBackups();

// Find the backup you want
const targetBackup = backups.find(b => b.metadata.note === 'Before theme customization');

// Restore it (with confirmation)
await masBackupManager.restoreBackup(targetBackup.id);
```

### 3. Export Backup for Sharing

```javascript
// Export backup as JSON file
await masBackupManager.exportBackup('1234567890_abc123');
// File will be downloaded: mas-backup-1234567890_abc123.json
```

### 4. Automatic Cleanup

```php
// Cleanup runs automatically after backup creation
// Or run manually:
$backup_service = MAS_Backup_Service::get_instance();
$deleted_count = $backup_service->cleanup_old_backups();
echo "Deleted $deleted_count old backups";
```

## Error Codes

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `backup_not_found` | Backup ID does not exist | 404 |
| `backup_creation_failed` | Failed to create backup | 500 |
| `backup_deletion_failed` | Failed to delete backup | 500 |
| `restore_failed` | Failed to restore backup | 500 |
| `pre_restore_backup_failed` | Failed to create pre-restore backup | 500 |
| `backup_validation_failed` | Backup data is invalid | 400 |
| `rest_forbidden` | User lacks permission | 403 |

## Best Practices

1. **Always create a backup before major changes**
   ```javascript
   await masBackupManager.createBackup('Before major update');
   ```

2. **Use descriptive notes**
   ```javascript
   await masBackupManager.createBackup('Before switching to dark theme');
   ```

3. **Let automatic cleanup handle old backups**
   - Don't manually delete automatic backups unless necessary
   - Manual backups are kept longer

4. **Test restore in development first**
   - Verify backup/restore works in your environment
   - Check that settings are correctly restored

5. **Monitor backup statistics**
   ```javascript
   const stats = await masRestClient.request('/backups/statistics');
   console.log('Total backups:', stats.data.total_backups);
   ```

## Troubleshooting

### Backup not found
- Check that the backup ID is correct
- Verify the backup hasn't been automatically cleaned up

### Restore fails
- Check WordPress error logs
- Verify backup data is valid
- Ensure sufficient permissions

### Cleanup not working
- Check retention policy settings
- Verify cron jobs are running
- Check for PHP errors

## Support

For issues or questions:
1. Check the completion report: `TASK-4-BACKUP-ENDPOINTS-COMPLETION.md`
2. Run the test script: `php test-task4-backup-endpoints.php`
3. Check WordPress debug logs
4. Review the source code documentation
