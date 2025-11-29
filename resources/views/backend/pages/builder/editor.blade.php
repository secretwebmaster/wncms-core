<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->title }} — Page Builder</title>

    <link href="https://unpkg.com/grapesjs/dist/css/grapes.min.css" rel="stylesheet" />
    <script src="https://unpkg.com/grapesjs"></script>

    <!-- OFFICIAL PLUGINS -->
    <script src="https://unpkg.com/grapesjs-preset-webpage"></script>
    <script src="https://unpkg.com/grapesjs-blocks-basic"></script>

    <!-- COMMUNITY PLUGINS -->
    <script src="https://unpkg.com/grapesjs-blocks-bootstrap5"></script>
    <script src="https://unpkg.com/grapesjs-style-gradient"></script>
    <script src="https://unpkg.com/grapesjs-style-filter"></script>
    <script src="https://unpkg.com/grapesjs-style-border"></script>
    <script src="https://unpkg.com/grapesjs-style-flexbox"></script>
    <script src="https://unpkg.com/grapesjs-parser-postcss"></script>
    <script src="https://unpkg.com/grapesjs-tables"></script>

    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }
        #gjs {
            height: 100vh;
        }
        #builder-save-btn {
            position: fixed;
            top: 12px;
            right: 15px;
            z-index: 99999;
            padding: 8px 14px;
            background: #0d6efd;
            border: none;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>

    <!-- ============================
        ✔ FULL zh_TW TRANSLATIONS
    ================================= -->
    <script>
        grapesjs.plugins.add('wncms-i18n-zhTW', (editor) => {
            editor.I18n.addMessages({
                zh_TW: {
                    // -------- Device Panel --------
                    "deviceManager.device.desktop": "桌面",
                    "deviceManager.device.tablet": "平板",
                    "deviceManager.device.mobilePortrait": "手機",

                    // -------- Panel Actions --------
                    "undo": "復原",
                    "redo": "重做",
                    "core:preview": "預覽",
                    "fullscreen": "全螢幕",
                    "export-template": "匯出模板",
                    "open-code": "原始碼",
                    "gjs-open-import-webpage": "匯入頁面",
                    "clear": "清空",
                    "save": "儲存",

                    // -------- Style Manager --------
                    "styleManager.sectors.general": "一般",
                    "styleManager.sectors.layout": "版面配置",
                    "styleManager.sectors.typography": "文字",
                    "styleManager.sectors.decorations": "外觀",
                    "styleManager.sectors.extra": "其他",

                    // -------- Trait Manager --------
                    "traitManager.label": "屬性",
                    "trait.placeholder": "輸入文字",

                    // -------- Layers --------
                    "layerManager.label": "圖層",

                    // -------- Blocks --------
                    "blocks.categories.basic": "基本元素",
                    "gjs-blocks-basic.text": "文字",
                    "gjs-blocks-basic.image": "圖片",
                    "gjs-blocks-basic.video": "影片",
                    "gjs-blocks-basic.link": "連結",
                    "gjs-blocks-basic.columns": "欄位佈局",

                    // -------- Bootstrap5 Blocks --------
                    "grapesjs-blocks-bootstrap5.container": "容器",
                    "grapesjs-blocks-bootstrap5.row": "列",
                    "grapesjs-blocks-bootstrap5.column": "欄位",

                    // -------- Tables plugin --------
                    "grapesjs-tables.table": "表格",
                    "grapesjs-tables.row": "表格列",
                    "grapesjs-tables.cell": "表格格子",
                }
            });
        });
    </script>
</head>

<body>

    <button id="builder-save-btn">保存</button>

    <div id="gjs"></div>

    <script>
        const editor = grapesjs.init({
            container: '#gjs',
            height: '100vh',
            locale: 'zh_TW',

            plugins: [
                'gjs-blocks-basic',
                'grapesjs-preset-webpage',
                'grapesjs-blocks-bootstrap5',
                'grapesjs-style-gradient',
                'grapesjs-style-filter',
                'grapesjs-style-border',
                'grapesjs-style-flexbox',
                'grapesjs-parser-postcss',
                'grapesjs-tables',

                // IMPORTANT: Load Translator last
                'wncms-i18n-zhTW'
            ],
        });

        // Required because some plugins overwrite labels after init
        editor.on("load", () => {
            editor.I18n.setLocale('zh_TW');
        });

        // ---------- SAVE ----------
        document.getElementById('builder-save-btn').onclick = () => {
            const payload = {
                html: editor.getHtml(),
                css: editor.getCss(),
                components: editor.getComponents(),
                styles: editor.getStyle(),
            };

            fetch('{{ route('pages.builder.save', $page->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ payload })
            })
            .then(r => r.json())
            .then(j => alert(j.success ? "已儲存" : "儲存失敗"));
        };
    </script>

</body>

</html>
