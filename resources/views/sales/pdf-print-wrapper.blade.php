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
            display: block;
        }
        #printLoading {
            position: fixed;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: #525659;
            color: #e2e2e2;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .print-spinner {
            width: 34px;
            height: 34px;
            border: 3px solid rgba(255,255,255,0.25);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: print-spin 0.8s linear infinite;
        }
        @keyframes print-spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div id="printLoading">
        <div class="print-spinner"></div>
        <span>Preparing print…</span>
    </div>
    <iframe id="pdfFrame" src="{{ $pdfUrl }}" style="display:none;"></iframe>
    <script>
        const iframe = document.getElementById('pdfFrame');
        const loadingEl = document.getElementById('printLoading');
        let printTriggered = false;

        function triggerPrint() {
            if (printTriggered) return;
            printTriggered = true;
            loadingEl.style.display = 'none';
            iframe.style.display = 'block';
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
