<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<style>

/* PAGE MARGINS */


/* BODY */
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 12px;
    color: #333;
}


/* HEADER */
@page {
    margin: 140px 40px 90px 40px;
}

.header {
    position: fixed;
    top: -120px;   /* Match your margin-top adjustment */
    left: 0;
    right: 0;
    height: 100px;
    text-align: center;
}

.header img {
    display: block;
    margin: 0 auto;
}

.header h2 {
    margin: 5px 0 0 0;
}

.footer {
    position: fixed;
    bottom: -70px;
    left: 0;
    right: 0;
    height: 50px;
}

.logo {
    height: 50px;
}

/* FOOTER */


/* PAGE NUMBER */
.pagenum:after {
    content: counter(page);
}

/* TABLE STYLE */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    page-break-inside: auto;
}

tr {
    page-break-inside: avoid;
    page-break-after: auto;
}
table, th, td {
    border: 1px solid #ccc;
}

th, td {
    padding: 6px;
}

th {
    background: #f2f2f2;
}

/* WATERMARK */
.watermark {
    position: fixed;
    top: 35%;
    left: 20%;
    opacity: 0.06;
    z-index: -1000;
}

.signature {
    margin-top: 40px;
}

.signature-line {
    border-top: 1px solid #000;
    width: 200px;
    margin-top: 40px;
}

</style>
</head>

<body>

    @if(Auth::user()->role == 'admin')
        @include('admin.reports.detail')
    @else
        @include('admin.reports.detail')
    @endif

</body>
</html>