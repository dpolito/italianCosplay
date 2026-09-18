(() => {
    'use strict';

    document.querySelectorAll('.blog-article-content table').forEach((table) => {
        const headerRows = table.tHead ? Array.from(table.tHead.rows) : [];
        const rows = Array.from(table.tBodies).flatMap((body) => Array.from(body.rows));
        if (headerRows.length !== 1 || rows.length === 0 || table.querySelector('table, tfoot')) return;

        const headers = Array.from(headerRows[0].cells);
        const cells = [...headers, ...rows.flatMap((row) => Array.from(row.cells))];
        if (headers.some((header) => header.tagName !== 'TH' || !header.textContent.trim())
            || cells.some((cell) => cell.colSpan !== 1 || cell.rowSpan !== 1)
            || rows.some((row) => row.cells.length !== headers.length)) return;

        table.setAttribute('role', 'table');
        table.tHead.setAttribute('role', 'rowgroup');
        headerRows[0].setAttribute('role', 'row');
        headers.forEach((header) => {
            header.setAttribute('scope', 'col');
            header.setAttribute('role', 'columnheader');
        });
        Array.from(table.tBodies).forEach((body) => body.setAttribute('role', 'rowgroup'));
        rows.forEach((row) => {
            row.setAttribute('role', 'row');
            Array.from(row.cells).forEach((cell, index) => {
                cell.setAttribute('role', cell.tagName === 'TH' ? 'rowheader' : 'cell');
                const label = document.createElement('span');
                label.className = 'blog-table-label';
                label.textContent = headers[index].textContent.trim();
                label.setAttribute('aria-hidden', 'true');
                cell.prepend(label);
            });
        });
        table.classList.add('blog-table-responsive');
    });
})();
