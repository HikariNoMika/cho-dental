import './bootstrap';

import Alpine from 'alpinejs';
import { renderAsync } from 'docx-preview';

window.Alpine = Alpine;

window.printDocx = async (url) => {
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        window.alert('Please allow popups to print this form.');
        return;
    }

    printWindow.document.write(`<!doctype html>
        <html>
            <head>
                <title>Print Claim Form</title>
                <style>
                    @page { margin: 0; }
                    html, body { margin: 0; padding: 0; background: #fff; }
                    .docx-wrapper { background: #fff; padding: 0; }
                    .docx-wrapper > section.docx { box-shadow: none; margin: 0 auto; }
                </style>
            </head>
            <body>
                <div id="docx-container"></div>
            </body>
        </html>`);
    printWindow.document.close();

    try {
        const response = await fetch(url, { credentials: 'same-origin' });

        if (!response.ok) {
            throw new Error(`Unable to download form (${response.status}).`);
        }

        const blob = await response.blob();
        const container = printWindow.document.getElementById('docx-container');

        await renderAsync(blob, container, null, {
            className: 'docx',
            inWrapper: true,
            ignoreWidth: false,
            ignoreHeight: false,
            breakPages: true,
        });

        await new Promise((resolve) => setTimeout(resolve, 500));

        printWindow.focus();
        printWindow.print();
    } catch (error) {
        console.error(error);
        printWindow.document.body.innerHTML = '<p style="font-family: sans-serif; padding: 24px;">Unable to prepare this form for printing. Please download the DOCX and print it from Word.</p>';
    }
};

Alpine.start();
