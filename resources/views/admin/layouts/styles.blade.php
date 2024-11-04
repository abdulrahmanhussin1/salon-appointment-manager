    <!-- Favicons -->
    <link href="{{asset('admin-assets')}}/assets/img/favicon.png" rel="icon">
    <link href="{{asset('admin-assets')}}/assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="{{asset('admin-assets')}}/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{asset('admin-assets')}}/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="{{asset('admin-assets')}}/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="{{asset('admin-assets')}}/assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <link href="{{asset('admin-assets')}}/assets/vendor/quill/quill.bubble.css" rel="stylesheet">
    <link href="{{asset('admin-assets')}}/assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="{{asset('admin-assets')}}/assets/vendor/simple-datatables/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css">


    <link rel="stylesheet" href="{{ asset('admin-assets/assets/vendor/select2-4.1.0-rc.0/dist/css/select2.min.css') }}">
    <!-- Template Main CSS File -->
    <link href="{{asset('admin-assets')}}/assets/css/style.css" rel="stylesheet">
    <style>
        #main {
    min-height: calc(100vh - 100px); /* Adjust the 100px based on your footer height */
    box-sizing: border-box;

}


/* Footer styles */
.footer {
    color: white;
    text-align: center;
    line-height: 10px; /* Vertically center the text in the footer */
    position: relative; /* Allow the footer to stay at the end of content */
}
.dataTables_filter {float: inline-end}
.pagination {float: right}

.dataTables_info{
    font-size: small;
    margin-top: 15px;
}
.pagination{
    margin-top: 10px;
    width: fit-content;
}
.dataTables_length label{
    font-size: small;
}
.dataTables_filter label{
font-size: small;
}
.dataTable thead {
    font-size: small;
}

.dataTable tbody td {
    font-size: small;
    width: fit-content;
}

.dropdown-toggle::after {
display: none;
}
.page-link {
    font-size: small;
}




/* Style the page length selector */
.dataTables_length {
    display: flex;
    align-items: center;
    margin-inline: 10px;
}




    </style>

@yield('css')
