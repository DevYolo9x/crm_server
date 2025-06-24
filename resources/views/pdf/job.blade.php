<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Job Description Template</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 14px;
            color: #333;
            padding: 30px;
            line-height: 1.6;
        }

        h1 {
            color: #000;
            font-size: 20px;
        }

        h2 {
            color: #2c3e50;
            font-size: 18px;
            margin-top: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .label {
            font-weight: bold;
            display: inline-block;
            width: 220px;
            vertical-align: top;
        }

        .value {
            display: inline-block;
            width: calc(100% - 230px);
        }



        .contact-info b {
            display: inline-block;
            width: 100px;
        }
    </style>
</head>

<body>
    <div>
        <img src="<?php echo $logo ?>" alt="Logo" />
    </div>
    <h1 style="text-align:center;text-transform: uppercase;">JOB DESCRIPTION / Thông tin tuyển dụng</h1>

    <div class="section">
        <h2>Position / Vị trí tuyển dụng</h2>
        <div class="content-block">
            {{ $position }}
        </div>
    </div>

    <div class="section">
        <h2>Company Profile / Thông tin công ty</h2>
        <div class="content-block">
            {!! $company_info !!}
        </div>

    </div>

    <div class="section">
        <h2>Job Description / Mô tả công việc</h2>
        <div class="content-block">
            {!! $job_description !!}
        </div>
    </div>

    <div class="section">
        <h2>Requirements / Yêu cầu</h2>
        <div class="content-block">
            {!! $requirements !!}
        </div>
    </div>

    <div class="section">
        <h2>Benefits / Phúc lợi</h2>
        <div class="content-block">
            {!! $benefits !!}
        </div>
    </div>

    <div class="section contact-info">
        <h2>Contact / Liên hệ</h2>
        <div><b>Email:</b> {{ $customer_email }}</div>
        <div><b>Phone:</b> {{ $customer_phone }}</div>
    </div>

</body>

</html>