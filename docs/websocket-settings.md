# WebSocket Configuration

The Edutorium Battle System uses a centralized configuration system to manage settings like the WebSocket URL. This allows administrators to update the WebSocket connection URL without modifying any code.

## How It Works

1. The WebSocket URL is stored in a `settings` table in the Supabase database.
2. The setting is identified by the key `websocket_url`.
3. PHP code fetches this URL using the `getWebSocketUrl()` function.
4. JavaScript code reads this URL from an HTML element with ID `websocket-url`.

## Updating the WebSocket URL

You can update the WebSocket URL in two ways:

### Using Supabase Dashboard

1. Log in to your Supabase dashboard.
2. Go to the table editor.
3. Navigate to the `settings` table.
4. Find the row with the key `websocket_url`.
5. Update the `value` column with your new WebSocket URL.
6. The changes will be applied immediately for new connections.

### Using SQL Query

Execute the following SQL query in the Supabase SQL editor:

```sql
UPDATE settings
SET value = 'wss://your-new-websocket-url',
    updated_at = CURRENT_TIMESTAMP
WHERE key = 'websocket_url';
```

## Implementation Details

### PHP Usage

To get the WebSocket URL in PHP code, use:

```php
$websocketUrl = getWebSocketUrl();
```

This function will return the URL from the database, or a default URL (`ws://localhost:8080`) if not found.

### JavaScript Usage

The WebSocket module will automatically use the URL specified in the HTML element with ID `websocket-url`:

```html
<span id="websocket-url"><?php echo getWebSocketUrl(); ?></span>
```

You can initialize the WebSocket connection using:

```javascript
// Initialize with the URL from the HTML element
BattleWebSocket.init().then(() => {
    console.log('Connected to WebSocket server');
}).catch(error => {
    console.error('Failed to connect to WebSocket server', error);
});

// Or specify a URL explicitly
BattleWebSocket.init('ws://custom-url:8080').then(() => {
    console.log('Connected to WebSocket server');
}).catch(error => {
    console.error('Failed to connect to WebSocket server', error);
});
```

## Testing the Connection

You can test the WebSocket connection using the `test-websocket.php` page, which:

1. Displays the current WebSocket URL from the database
2. Allows you to connect to the WebSocket server
3. Tests basic WebSocket functionality like sending messages and receiving responses

## Troubleshooting

If you encounter connection issues after updating the WebSocket URL:

1. Ensure the URL is valid and the WebSocket server is running at that address
2. Check for typos in the URL (e.g., `ws://` vs `wss://`)
3. Verify that the setting was properly saved in the database
4. Clear your browser cache to ensure the new URL is being loaded 