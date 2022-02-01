<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset=UTF-8>
    <title>Haramien Report</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" 
    integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" 
    crossorigin="anonymous">
  </head>
  <style>
      .img{
        background-image: url("{{ asset('sora.jpeg')}}");
        background-color: #cccccc;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        }
      .report-haramin-body{
        background-size: 100% 100%;
        background-repeat: no-repeat;
        }
        .prim3 .padding-report {
        padding: 60px 85px
        }
        .prim4 .padding-report {
        padding: 0px 20px 20px;
        }
        .prim3 .titles{
        margin-left: 155px;
        }
        .prim4 .titles{
        margin-top: 40px;
        margin-left: 70px;
        }
        .title-info{
        font-size: 25px;
        }
        .title-header{
        margin-top: 50px;
        margin-left: 40px;
        font-weight: bold;
        font-size: 30px;
        }
        .title{
        font-weight: bold;
        font-size: 30px;
        margin-top: 25px;
        margin-left: 60px;
        }
        .subject{
        font-size: 40px;
        }
        .table{
        margin-top: 31px;
        width: 70%;
        }
        .prim4 .table{
        margin-bottom: 55px;
        }
        th, td{
        padding: 6px;
        font-size: 16px;
        text-align: center;
        border: 1px solid #000 !important;
        }
        .scale{
        border-radius: 50%;
        height: 40px;
        margin: 0px auto;
        width: 80px;
        }
        .colors{
        margin-left: 35px;
        }
        .prim4 .colors{
        margin-bottom: 50px;
        }
        .color{
        padding: 0px;
        }
        .color div{
        padding: 12px;
        font-size: 16px;
        font-weight: bold;
        text-align: center;
        }
        .prim4 ,.color div{
        font-size: 18px;
        }
        .student-info{
        margin-top: 20px;
        }
        .student-name-title{
        font-weight: bold;
        margin-left: 50px;
        font-size: 35px;
        margin-top: 10px;
        }
        .student-name{
        padding: 9px 20px;
        font-size: 20px;
        border: 2px solid;
        margin-left: 30px;
        text-align: left;
        }
        .fotter-title{
        margin-top: 20px;
        }
        .fotter-title h3{
        font-weight: bold;
        font-size: 20px;
        }
  </style>
  <body class='img'>
    <?php 
        // foreach($result as $value)
        $result=json_decode(json_encode($result), true);
        $imgSrc = "./sora.jpeg";
        // dd($result->header('Authorization'));
        // dd($result['original']['body']['enroll']);
    ?>
    <div class="report-card-haramain" style="direction: ltr;">
    <!-- <img src="{{ asset('sora.jpeg')}}" alt="ahmed"> -->
        <div class="container report-haramin-body">
            <div class="report-card-haramain" style="direction: ltr;">
                <div class="container report-haramin-body">
                    <div class="padding-report">
                        <div class="row ">
                            <div class="col-12">
                                <div class="titles" >
                                    <h3 class="title-info text-left">Cairo Governorate</h3>
                                    <h3 class="title-info text-left">New Cairo Directorate</h3>
                                    <h3 class="title-info text-left">El Haramien Private Schools</h3>
                                </div>

                                <h1 class="title-header text-center"> The First Term Evaluation year 2021 - 2022 </h1>
                                <?php 
                                    foreach($result['original']['body']['enroll'] as $enroll)
                                    {
                                ?>
                                        <h1 class="title text-center " style="margin-right: 150px">Grade: <?php echo $enroll['levels']['name']?>   </h1>
                                <?php 
                                    }
                                ?>
                            </div>

                            <div class="">
                                <table class="table" style="margin-left: -85px">
                                    <?php 
                                        foreach($result['original']['body']['enroll'] as $enroll)
                                        {
                                    ?>
                                            <thead>
                                                <tr>
                                                    <th>Subject</th>
                                                    <?php 
                                                        foreach($enroll['courses']['grade_category'] as $item)
                                                        {
                                                    ?>
                                                            <th style="padding: 5px !important;vertical-align: middle;"> <?php echo $item['name'] ?></th>
                                                    <?php
                                                        }
                                                    ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <th >Student's mark</th>
                                                    <?php
                                                        foreach($enroll['courses']['grade_category'] as $item)
                                                        {
                                                    ?>
                                                            <th> 
                                                            <div class="scale"
                                                        style="background-color:
                                                        <?php if( $item['user_grades'][0]['scale'] == 'Excellent'){echo 'blue';}
                                                            elseif( $item['user_grades'][0]['scale'] == 'Very good'){echo 'green';}
                                                            elseif( $item['user_grades'][0]['scale'] == 'Accptable'){echo 'yellow';}
                                                            elseif( $item['user_grades'][0]['scale'] == 'Bad'){echo 'red';} else '-';?>"> 
                                                            <?php 
                                                                        // if($item['user_  grades'][0]['grade'] ==null){echo '-';} else{ echo $item['user_grades'][0]['grade'];} 
                                                                    ?>
                                                        </div>
                                                            </th>
                                                    <?php
                                                        }
                                                    ?>
                                                </tr>
                                            </tbody>
                                    <?php 
                                        }
                                    ?>
                                </table>
                            </div>

                            <div class="col-8 row colors text-center">
                                <div class="col color">
                                    <div style="background: blue;">Excellent</div>
                                </div>
                                <div class="col color">
                                    <div style="background: green;">Very good</div>
                                </div>
                                <div class="col color">
                                    <div style="background: yellow;">Acceptable</div>
                                </div>
                                <div class="col color">
                                    <div style="background: red;">Bad</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex student-info">
                                    <h3 class="student-name-title">Student's Name /</h3>
                                    <div style="width: 45%;">
                                        <h4 class="student-name"><?php echo $result['original']['body']['fullname'] ?></h4>
                                    </div>
                                </div>
                                <div class="col-10">
                                    <div class="d-flex justify-content-between fotter-title">
                                        <div>
                                            <h3>Head of control committee</h3>
                                            <h3>Mohamed mohamed Ali</h3>
                                        </div>
                                        <div>
                                            <h3>Schools Manager</h3>
                                            <h3>Sahar Halawa</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </body>
  <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
  <script></script>
</html>