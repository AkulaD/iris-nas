<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRiS-NAS | Cloud Storage</title>

    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="data/style.css">
</head>
<body>

<div id="loading-screen" class="loading-overlay">
    <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<div id="download-toast" class="position-fixed top-0 start-50 translate-middle-x p-3 mt-3" style="z-index: 1060; display: none;">
    <div class="toast show align-items-center text-white bg-primary border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex p-2">
            <div class="toast-body d-flex align-items-center gap-3 fs-6">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <span>Menyiapkan file untuk didownload...</span>
            </div>
        </div>
    </div>
</div>

<div class="app-wrapper">