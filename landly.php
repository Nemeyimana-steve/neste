<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>simple calculator</title>
</head>
<body>
    <h1>simple calculator</h1> 
    <label for name="first number">first number</label> 
    <input type="text"> <br><br> 
    <label for name="second number">second numer</label>
    <input type="text"> <br><br>
 <button name="options"> 
    <button name="addition ">+</button>
    <button name="substraction ">-</button>
    <button name="multiplication ">*</button>
    <button name="division ">/</button>
    <button name ="modulus ">%</button>
</button>
</body>
</html>
<?php
if(isset($_POST['firstNumber']) && ($_POST['secondNumber'])){
    $number1=$_post['fisrtNumber1'];
    $a=(float)$number1;
    $number2=$_post['secondNumber'];
    $b=(float)$number2;



else if (isset($_post['addition'])){
    echo $a+$b;
}
else if (isset($_post['substraction'])){
    echo $a-$b;
}
else if (isset($_post['multiplication'])){
    echo $a*$b;
    }
    else if (isset($_post['division'])){
    echo $a/$b;
    }
else if (isset($_post['modulus'])){
    echo $a%$b;
} 
else {
    echo"please enter firstNumber";

}
}
?> 

