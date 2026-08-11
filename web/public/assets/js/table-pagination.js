(function () {
    const pageSizes = [20, 50, 100];
    const paginators = [];

    function visiblePages(current, total) {
        const pages = new Set([1, total]);
        for (let page = current - 2; page <= current + 2; page += 1) {
            if (page >= 1 && page <= total) {
                pages.add(page);
            }
        }
        return [...pages].sort((a, b) => a - b);
    }

    function createButton(label, className, onClick) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = className;
        button.textContent = label;
        button.addEventListener('click', onClick);
        return button;
    }

    function paginateTable(table) {
        if (table.dataset.noClientPagination === 'true') {
            return null;
        }

        const tbody = table.tBodies[0];
        if (!tbody) {
            return null;
        }

        const rows = Array.from(tbody.rows);
        if (rows.length <= pageSizes[0]) {
            return null;
        }

        let currentPage = 1;
        let pageSize = pageSizes[0];

        const wrap = table.closest('.table-wrap');
        const controls = document.createElement('div');
        controls.className = 'table-pagination';

        const summary = document.createElement('span');
        summary.className = 'table-pagination-summary';

        const sizeLabel = document.createElement('label');
        sizeLabel.textContent = 'Rows';

        const sizeSelect = document.createElement('select');
        pageSizes.forEach((size) => {
            const option = document.createElement('option');
            option.value = String(size);
            option.textContent = String(size);
            sizeSelect.appendChild(option);
        });
        sizeLabel.appendChild(sizeSelect);

        const nav = document.createElement('div');
        nav.className = 'table-pagination-nav';

        controls.append(summary, sizeLabel, nav);
        wrap.insertAdjacentElement('afterend', controls);

        function render() {
            const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
            currentPage = Math.min(currentPage, totalPages);
            const start = (currentPage - 1) * pageSize;
            const end = Math.min(start + pageSize, rows.length);

            rows.forEach((row, index) => {
                row.hidden = index < start || index >= end;
            });

            summary.textContent = `${start + 1}-${end} dari ${rows.length} data`;
            nav.replaceChildren();

            const previous = createButton('Prev', 'table-page-button', () => {
                currentPage = Math.max(1, currentPage - 1);
                render();
            });
            previous.disabled = currentPage === 1;
            nav.appendChild(previous);

            let lastPage = 0;
            visiblePages(currentPage, totalPages).forEach((page) => {
                if (page - lastPage > 1) {
                    const dots = document.createElement('span');
                    dots.className = 'table-page-dots';
                    dots.textContent = '...';
                    nav.appendChild(dots);
                }
                const button = createButton(String(page), 'table-page-button', () => {
                    currentPage = page;
                    render();
                });
                button.classList.toggle('active', page === currentPage);
                nav.appendChild(button);
                lastPage = page;
            });

            const next = createButton('Next', 'table-page-button', () => {
                currentPage = Math.min(totalPages, currentPage + 1);
                render();
            });
            next.disabled = currentPage === totalPages;
            nav.appendChild(next);
        }

        sizeSelect.addEventListener('change', () => {
            pageSize = Number(sizeSelect.value);
            currentPage = 1;
            render();
        });

        render();

        return {
            showAll() {
                rows.forEach((row) => {
                    row.hidden = false;
                });
            },
            render,
        };
    }

    function init() {
        document.querySelectorAll('.table-wrap > table').forEach((table) => {
            const paginator = paginateTable(table);
            if (paginator) {
                paginators.push(paginator);
            }
        });
    }

    window.addEventListener('beforeprint', () => {
        paginators.forEach((paginator) => paginator.showAll());
    });

    window.addEventListener('afterprint', () => {
        paginators.forEach((paginator) => paginator.render());
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
