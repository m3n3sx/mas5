<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test AJAX Data Format</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; }
        .test-form { margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 4px; }
        .test-form input, .test-form select { margin: 10px 0; padding: 8px; width: 100%; }
        .test-form button { padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .result { margin: 20px 0; padding: 15px; background: #e7f5ff; border-left: 4px solid #0073aa; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test AJAX Data Format</h1>
        <p>Ten test sprawdza jak dane są wysyłane przez AJAX</p>
        
        <div class="test-form">
            <h2>Formularz Testowy</h2>
            <form id="test-form">
                <label>Menu Background Color:</label>
                <input type="color" name="menu_background" value="#ff0000">
                
                <label>Menu Width:</label>
                <input type="number" name="menu_width" value="250" min="100" max="400">
                
                <label>Menu Text Color:</label>
                <input type="color" name="menu_text_color" value="#ffffff">
                
                <label>Enable Animations:</label>
                <select name="enable_animations">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
                
                <label>Admin Bar Height:</label>
                <input type="number" name="admin_bar_height" value="40">
                
                <button type="submit">Test Submit</button>
            </form>
        </div>
        
        <div class="result" id="result" style="display:none;">
            <h3>Wynik:</h3>
            <div id="result-content"></div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#test-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            
            // Test 1: serialize() - ZŁY sposób
            const serialized = $form.serialize();
            console.log('=== TEST 1: serialize() ===');
            console.log('Type:', typeof serialized);
            console.log('Value:', serialized);
            
            // Test 2: serializeArray() - DOBRY sposób
            const serializedArray = $form.serializeArray();
            console.log('\n=== TEST 2: serializeArray() ===');
            console.log('Type:', typeof serializedArray);
            console.log('Value:', serializedArray);
            
            // Test 3: Rozpakowanie do obiektu - NAJLEPSZY sposób
            const postData = {
                action: 'mas_v2_save_settings',
                nonce: 'test_nonce_123'
            };
            
            $.each(serializedArray, function(i, field) {
                postData[field.name] = field.value;
            });
            
            console.log('\n=== TEST 3: Rozpakowany obiekt ===');
            console.log('Type:', typeof postData);
            console.log('Value:', postData);
            console.log('Keys:', Object.keys(postData));
            
            // Pokaż wyniki
            let html = '<h4>Test 1: serialize() (ZŁY)</h4>';
            html += '<pre>' + JSON.stringify({
                type: typeof serialized,
                value: serialized
            }, null, 2) + '</pre>';
            
            html += '<h4>Test 2: serializeArray() (LEPSZY)</h4>';
            html += '<pre>' + JSON.stringify(serializedArray, null, 2) + '</pre>';
            
            html += '<h4>Test 3: Rozpakowany obiekt (NAJLEPSZY)</h4>';
            html += '<pre>' + JSON.stringify(postData, null, 2) + '</pre>';
            
            html += '<h4>Co PHP dostanie:</h4>';
            html += '<pre>';
            html += 'Z serialize():\n';
            html += '$_POST[\'settings\'] = "' + serialized + '"\n\n';
            html += 'Z rozpakowanego obiektu:\n';
            $.each(postData, function(key, value) {
                html += '$_POST[\'' + key + '\'] = \'' + value + '\'\n';
            });
            html += '</pre>';
            
            $('#result-content').html(html);
            $('#result').show();
        });
    });
    </script>
</body>
</html>
