<!DOCTYPE html>
<html>
<head>
    <title>Simple PHP Calculator</title>
</head>
<body>

    <h2>Simple Calculator</h2>

    <form method="post" action="">
        <input type="number" name="num1" placeholder="Enter first number" required>
        <select name="operator">
            <option value="add">+</option>
            <option value="subtract">-</option>
            <option value="multiply">*</option>
            <option value="divide">/</option>
        </select>
        <input type="number" name="num2" placeholder="Enter second number" required>
        <button type="submit" name="calculate">Calculate</button>
    </form>

    <?php
    if (isset($_POST['calculate'])) {
        $num1 = $_POST['num1'];
        $num2 = $_POST['num2'];
        $operator = $_POST['operator'];
        $result = '';

        switch ($operator) {
            case 'add':
                $result = $num1 + $num2;
                break;
            case 'subtract':
                $result = $num1 - $num2;
                break;
            case 'multiply':
                $result = $num1 * $num2;
                break;
            case 'divide':
                if ($num2 != 0) {
                    $result = $num1 / $num2;
                } else {
                    $result = "Cannot divide by zero.";
                }
                break;
            default:
                $result = "Invalid operator.";
                break;
        }

        echo "<h3>Result: $result</h3>";
    }
    ?>

</body>
</html>