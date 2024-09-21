<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<style>
    .sidebar {
        width: 100%;
        max-width: 300px;
        position: fixed;
        height: 100%;
        top: 0;
        left: 0;
    }
    .content-container {
        padding-left: 20px;
        padding-right: 20px;
    }
    .content {
        margin-top: 70px;
    }
    .right-buttons {
        position: fixed;
        right: 10px;
        top: 70px;
        z-index: 1000;
    }
    @media (max-width: 767.98px) {
        .right-buttons {
            position: static;
            width: 100%;
            margin-top: 20px;
        }
        .sidebar {
            position: static;
        }
        .content-container {
            padding-left: 0;
            padding-right: 0;
        }
    }
</style>
