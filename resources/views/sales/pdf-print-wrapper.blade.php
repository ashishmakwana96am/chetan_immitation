<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Print Invoice' }}</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #525659;
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
    <iframe id="pdfFrame" src="{{ $pdfUrl }}"></iframe>
    <script>
        const iframe = document.getElementById('pdfFrame');
        let printTriggered = false;

        function triggerPrint() {
            if (printTriggered) return;
            printTriggered = true;
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (e) {
                window.print();
            }
        }

        iframe.onload = function() {
            setTimeout(triggerPrint, 500);
        };

        setTimeout(triggerPrint, 1500);
    </script>
</body>
</html>
