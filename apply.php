<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>register</title>
    <style>
        a{
            text-decoration:none;
            margin-left:100px;
        }
        label{
            font-style: italic;
            font-weight: bold;
        }
        input{
            width: 250px;
            height: 30px;
            border-radius: 9px;
        }
        h1{
            text-align: center;
        }
    </style>
</head>
<body>
    <form action="insert.php" method="POST">
         <h1>if you satisfied to this system and also ,if you have all requirements make register</h1><br></br>
    <center>
        <p>fill this registration form.</p><br>
        <label for="full_name">full_name</label><br>
        <input type="text"name="full_name"required><br>
        <label for="email">email</label><br>
        <input type="email"name="email"required><br>
        <label for="password">password</label><br>
        <input type="password"name="password"required><br>
        <label for="username">user_name</label><br>
        <input type="text"name="username"required><br>
        <label for="phone_number">phone_number</label><br>
       <input type="number"name="phone_number"required><br>
       <p>enter your gender/sex.</p><br>
       <input type="radio"name="gender"value="male">male<br>
       <input type="radio"name="gender"value="female">female<br>
       <p>enter your marks</p>
       <input type="number"name="marks"required><br>
       <p>which combination have been studied?.</p>
       select one
       <select name="course" >
        <option value="physics,chemistry,and biology">PCB</option>
        <option value="physics,chemistry,and mathematics">PCM</option>
        <option value="mathematics,physics,and geography">MPG</option>
        <option value="mathematics,economics,and geography">MEG</option>
        <option value="mathematics,chemistry,and biology">MCB</option>
        <option value="mathematics,economics,and computer">MEC</option>
        <option value="literature,kiswahili,and french">LKF</option>
        <option value="software development">SOD</option>
        <option value="multimedia production">MMP</option>
        <option value="tourism and hospitality">TOUR</option>
        <option value="buildings constructions">BDC</option>
        <option value="accounting">ACC</option>
        <option value="electronics and telecomunication">ELT</option>
        <option value="networking internet and technology">NIT</option>
        
       </select>


        </center>
        
    <a href="home.php">back</a>

    </form>
</body>
</html>