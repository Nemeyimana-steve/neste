<!DOCTYPE html>
<html>
<head>
    <title>Temperature Converter</title>
</head>
<body>

    <h2>Temperature Converter</h2>

    <form method="post" action="">
        <label for="temperature">Enter Temperature:</label>
        <input type="text" id="temperature" name="temperature" placeholder="e.g., 32.5" required>
        <br><br>
        <label for="from_unit">Convert From:</label>
        <select id="from_unit" name="from_unit">
            <option value="celsius">Celsius</option>
            <option value="fahrenheit">Fahrenheit</option>
        </select>
        <br><br>
        <label for="to_unit">Convert To:</label>
        <select id="to_unit" name="to_unit">
            <option value="fahrenheit">Fahrenheit</option>
            <option value="celsius">Celsius</option>
        </select>
        <br><br>
        <button type="submit" name="convert">Convert</button>
    </form>

    <?php
    if (isset($_POST['convert'])) {
        $temperature = $_POST['temperature'];
        $from_unit = $_POST['from_unit'];
        $to_unit = $_POST['to_unit'];
        $result = '';
        $error = '';

        // Validation
        if (!is_numeric($temperature)) {
            $error = "Error: Please enter a valid number.";
        } elseif ($from_unit === $to_unit) {
            $error = "Error: The 'from' and 'to' units must be different.";
        } else {
            // Conversion logic
            if ($from_unit === 'celsius' && $to_unit === 'fahrenheit') {
                $converted_temp = ($temperature * 9/5) + 32;
                $result = "$temperature °C is " . round($converted_temp, 2) . " °F.";
            } elseif ($from_unit === 'fahrenheit' && $to_unit === 'celsius') {
                $converted_temp = ($temperature - 32) * 5/9;
                $result = "$temperature °F is " . round($converted_temp, 2) . " °C.";
            }
        }

        // Display results or errors
        if ($error) {
            echo "<h3 style='color: red;'>$error</h3>";
        } elseif ($result) {
            echo "<h3>$result</h3>";
        }
    }
    ?>

</body>
</html>