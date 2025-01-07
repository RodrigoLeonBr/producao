// Variáveis globais
let currentPage = 1;
const itemsPerPage = 50;
let totalItems = 0;
let currentData = [];

// Função para obter filtros selecionados
function getFilters() {
    const filters = [];
    const filterContainers = document.querySelectorAll('.filter-container');
    
    filterContainers.forEach(container => {
        const field = container.querySelector('.filter-field').value;
        const operator = container.querySelector('.filter-operator').value;
        const value = container.querySelector('.filter-value').value;
        
        if (field && operator && value) {
            filters.push({
                column: field,
                operator: operator,
                value: value
            });
        }
    });
    
    return filters;
}

// Função para obter colunas selecionadas
function getSelectedColumns() {
    const selectedColumns = [];
    const checkedColumns = document.querySelectorAll('.form-check-input:checked');
    
    checkedColumns.forEach(checkbox => {
        selectedColumns.push({
            key: checkbox.value,
            label: checkbox.getAttribute('data-label'),
            table: checkbox.getAttribute('data-table'),
            isTotal: checkbox.getAttribute('data-is-total') === 'true'
        });
    });
    
    return selectedColumns;
}

// Função para gerar o SQL
function generateSQL() {
    const selectedColumns = getSelectedColumns();
    if (selectedColumns.length === 0) return null;

    const filters = getFilters();
    
    // Determinar quais tabelas precisam de JOIN
    const tables = new Set();
    selectedColumns.forEach(col => {
        // Verifica se a coluna pertence a uma tabela específica
        if (col.table === 'prestador' || col.key.startsWith('prestador.')) {
            tables.add('prestador');
        }
        if (col.table === 'procedimento' || col.key.startsWith('procedimento.')) {
            tables.add('procedimento');
        }
        if (col.table === 'cbo' || col.key.startsWith('cbo.')) {
            tables.add('cbo');
        }
        if (col.table === 'grupo' || col.key.startsWith('grupo.')) {
            tables.add('grupo');
        }
        if (col.table === 'subgrupo' || col.key.startsWith('subgrupo.')) {
            tables.add('subgrupo');
        }
        if (col.table === 'forma' || col.key.startsWith('forma.')) {
            tables.add('forma');
        }
    });

    // Construir a consulta SQL
    let sql = 'SELECT ';
    
    // Adicionar colunas selecionadas
    const columnsSql = selectedColumns.map(col => {
        // Tratar campos totalizáveis
        if (col.isTotal) {
            return `SUM(${col.key}) as ${col.key}`;
        }
        // Tratar campos com referência de tabela
        if (col.key.includes('.')) {
            const [table, field] = col.key.split('.');
            return `${table}.${field} as "${col.key}"`;
        }
        return col.key;
    }).join(', ');
    
    sql += columnsSql;
    if (tables.has('prestador')) {
        sql += ', prestador.ativo';
    }
    
    // FROM e JOINs
    sql += ' FROM s_prd';
    if (tables.has('prestador')) {
        sql += ' LEFT JOIN prestador ON s_prd.prd_uid = prestador.re_cunid';
    }
    if (tables.has('procedimento')) {
        sql += ' LEFT JOIN procedimento ON s_prd.prd_pa = procedimento.codigo';
    }
    if (tables.has('cbo')) {
        sql += ' LEFT JOIN cbo ON s_prd.prd_cbo = cbo.CBO';
    }
    if (tables.has('grupo')) {
        sql += ' LEFT JOIN grupo ON SUBSTRING(s_prd.prd_pa, 1, 2) = grupo.co_grupo';
    }
    if (tables.has('subgrupo')) {
        sql += ' LEFT JOIN subgrupo ON SUBSTRING(s_prd.prd_pa, 1, 4) = subgrupo.co_sub_grupo';
    }
    if (tables.has('forma')) {
        sql += ' LEFT JOIN forma ON SUBSTRING(s_prd.prd_pa, 1, 6) = forma.co_forma';
    }
    
    // WHERE
    if (filters.length > 0) {
        sql += ' WHERE ' + filters.map(filter => {
            let value = filter.value;
            
            // Tratar operadores LIKE
            if (filter.operator === 'LIKE' || filter.operator === 'NOT LIKE') {
                value = `'%${value}%'`;
            }
            // Tratar strings
            else if (!['number', 'boolean'].includes(typeof value)) {
                value = `'${value}'`;
            }
            
            return `${filter.column} ${filter.operator} ${value}`;
        }).join(' AND ');
    }
    
    // GROUP BY
    const groupByColumns = selectedColumns
        .filter(col => !col.isTotal)
        .map(col => {
            if (col.key === 'descricao') {
                return 'cbo.DESCRICAO';
            }
            return col.key;
        });
    
    if (groupByColumns.length > 0) {
        sql += ' GROUP BY ' + groupByColumns.join(', ');
    }
    
    return sql;
}

// Função para executar a consulta
async function executeQuery() {
    const executeButton = document.getElementById('executeQuery');
    const originalContent = executeButton.innerHTML;
    
    try {
        // Mostrar indicador de carregamento
        executeButton.innerHTML = '<i class="fas fa-spinner loading-spinner"></i> Executando...';
        executeButton.disabled = true;
        
        const selectedColumns = getSelectedColumns();
        console.log('Colunas selecionadas:', selectedColumns);
        
        if (selectedColumns.length === 0) {
            alert('Por favor, selecione pelo menos uma coluna para consultar.');
            return;
        }

        const sql = generateSQL();
        console.log('SQL gerado:', sql);
        
        // Mostrar SQL gerado
        const sqlPreview = document.getElementById('sql-preview');
        if (sqlPreview) {
            sqlPreview.textContent = sql;
        }
        
        if (!sql) {
            alert('Erro ao gerar SQL. Verifique as colunas selecionadas.');
            return;
        }

        const queryData = {
            sql: sql,
            page: currentPage,
            itemsPerPage: itemsPerPage
        };
        
        console.log('Dados enviados:', queryData);

        const response = await fetch('actions/execute_query.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(queryData)
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Resposta do servidor:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Resposta do servidor:', data);
        
        if (data.error) {
            throw new Error(data.error);
        }

        // Atualizar dados e interface
        currentData = data.results || [];
        totalItems = data.total || 0;
        
        console.log('Dados para exibir:', currentData);
        console.log('Total de itens:', totalItems);
        
        // Atualizar a tabela com os resultados
        updateTable(currentData);
        
        // Atualizar a paginação
        updatePagination(totalItems);
        
        // Habilitar botões de exportação
        enableExportButtons();
        
    } catch (error) {
        console.error('Erro completo:', error);
        alert('Erro na execução da consulta: ' + error.message);
        
        // Limpar SQL em caso de erro
        const sqlPreview = document.getElementById('sql-preview');
        if (sqlPreview) {
            sqlPreview.textContent = '';
        }
    } finally {
        // Restaurar botão ao estado original
        executeButton.innerHTML = originalContent;
        executeButton.disabled = false;
    }
}

// Função para atualizar a tabela
function updateTable(data) {
    const resultsContainer = document.getElementById('results-container');
    const table = document.getElementById('resultsTable');
    if (!table) return;
    
    // Limpar tabela
    table.innerHTML = '';
    
    if (!data || data.length === 0) {
        const emptyRow = document.createElement('tr');
        const emptyCell = document.createElement('td');
        emptyCell.colSpan = '100%';
        emptyCell.textContent = 'Nenhum resultado encontrado';
        emptyCell.className = 'text-center py-3';
        emptyRow.appendChild(emptyCell);
        table.appendChild(emptyRow);
        return;
    }
    
    // Mostrar o container de resultados
    if (resultsContainer) {
        resultsContainer.style.display = 'block';
    }
    
    // Obter colunas selecionadas na ordem correta
    const selectedColumns = getSelectedColumns();
    
    // Criar cabeçalho
    const thead = document.createElement('thead');
    const headerRow = document.createElement('tr');
    
    selectedColumns.forEach(column => {
        const th = document.createElement('th');
        th.textContent = column.label;
        th.className = 'align-middle';
        headerRow.appendChild(th);
    });
    
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    // Criar corpo da tabela
    const tbody = document.createElement('tbody');
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        
        selectedColumns.forEach(column => {
            const td = document.createElement('td');
            let value;
            
            // Lidar com campos de tabelas relacionadas
            if (column.key.includes('.')) {
                const [table, field] = column.key.split('.');
                // Tentar obter o valor usando a chave completa ou apenas o campo
                value = row[column.key] || row[field] || '';
            } else {
                value = row[column.key];
            }
            
            // Formatação especial para valores numéricos
            if (column.isTotal) {
                if (column.key === 'prd_vl_p') {
                    value = formatCurrency(value);
                } else {
                    value = formatNumber(value);
                }
            }
            
            td.textContent = value || '';
            td.className = 'align-middle';
            tr.appendChild(td);
        });
        
        tbody.appendChild(tr);
    });
    
    table.appendChild(tbody);
}

// Função para atualizar a paginação
function updatePagination(totalItems) {
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';

    if (totalPages <= 1) return;

    const ul = document.createElement('ul');
    ul.className = 'pagination';

    // Primeiro
    ul.appendChild(createPageItem('«', 1, currentPage === 1, 'Primeira página'));

    // Anterior
    ul.appendChild(createPageItem('‹', currentPage - 1, currentPage === 1, 'Anterior'));

    // Calcular intervalo de páginas
    let startPage = Math.max(1, currentPage - 3);
    let endPage = Math.min(totalPages, currentPage + 3);

    // Ajustar intervalo se estiver próximo ao início ou fim
    if (currentPage <= 4) {
        endPage = Math.min(7, totalPages);
    }
    if (currentPage > totalPages - 4) {
        startPage = Math.max(1, totalPages - 6);
    }

    // Adicionar primeira página e reticências se necessário
    if (startPage > 1) {
        ul.appendChild(createPageItem('1', 1));
        if (startPage > 2) {
            ul.appendChild(createEllipsis());
        }
    }

    // Páginas numeradas
    for (let i = startPage; i <= endPage; i++) {
        const li = document.createElement('li');
        li.className = `page-item${currentPage === i ? ' active' : ''}`;
        
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = i;
        a.onclick = (e) => {
            e.preventDefault();
            if (currentPage !== i) {
                currentPage = i;
                executeQuery();
            }
        };
        
        li.appendChild(a);
        ul.appendChild(li);
    }

    // Adicionar última página e reticências se necessário
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            ul.appendChild(createEllipsis());
        }
        ul.appendChild(createPageItem(totalPages.toString(), totalPages));
    }

    // Próximo
    ul.appendChild(createPageItem('›', currentPage + 1, currentPage === totalPages, 'Próximo'));

    // Último
    ul.appendChild(createPageItem('»', totalPages, currentPage === totalPages, 'Última página'));

    pagination.appendChild(ul);
}

function createPageItem(text, page, disabled = false, title = '') {
    const li = document.createElement('li');
    li.className = `page-item${disabled ? ' disabled' : ''}`;
    
    const a = document.createElement('a');
    a.className = 'page-link';
    a.href = '#';
    a.textContent = text;
    if (title) a.title = title;
    
    if (!disabled) {
        a.onclick = (e) => {
            e.preventDefault();
            if (currentPage !== page) {
                currentPage = page;
                executeQuery();
            }
        };
    }
    
    li.appendChild(a);
    return li;
}

function createEllipsis() {
    const li = document.createElement('li');
    li.className = 'page-item disabled';
    
    const span = document.createElement('span');
    span.className = 'page-link';
    span.textContent = '...';
    
    li.appendChild(span);
    return li;
}

// Função para formatar números
function formatNumber(value) {
    if (value === null || value === undefined) return '';
    return new Intl.NumberFormat('pt-BR').format(value);
}

// Função para formatar moeda
function formatCurrency(value) {
    if (value === null || value === undefined) return '';
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

// Função para formatar números no formato brasileiro
function formatNumberBR(value) {
    if (value === null || value === undefined || isNaN(value)) return '';
    return Number(value).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Função para formatar números no CSV
function formatNumberCSV(value) {
    if (value === null || value === undefined || isNaN(value)) return '';
    return Number(value).toFixed(2).replace('.', ',');
}

// Função para exportar para CSV
function exportToCSV() {
    if (!currentData || currentData.length === 0) return;
    
    const selectedColumns = getSelectedColumns();
    
    // Criar cabeçalho
    const header = selectedColumns.map(col => col.label).join(';');
    
    // Criar linhas de dados
    const rows = currentData.map(row => {
        return selectedColumns.map(col => {
            let value = row[col.key];
            if (['prd_qt_a', 'prd_qt_p', 'prd_vl_p'].includes(col.key)) {
                value = formatNumberCSV(value);
            }
            return value || '';
        }).join(';');
    });
    
    // Juntar tudo
    const csv = [header, ...rows].join('\n');
    
    // Criar blob e link para download
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'exportacao_' + new Date().toISOString().split('T')[0] + '.csv';
    link.click();
}

// Função para exportar para XLS
function exportToXLS() {
    if (!currentData || currentData.length === 0) return;
    
    const selectedColumns = getSelectedColumns();
    
    // Criar array de dados para o XLSX
    const data = currentData.map(row => {
        const newRow = {};
        selectedColumns.forEach(col => {
            let value = row[col.key];
            if (['prd_qt_a', 'prd_qt_p', 'prd_vl_p'].includes(col.key)) {
                value = formatNumberBR(value);
            }
            newRow[col.label] = value || '';
        });
        if (row.hasOwnProperty('ativo')) {
            newRow['Ativo'] = row.ativo ? 'Sim' : 'Não';
        }
        return newRow;
    });
    
    // Criar workbook
    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Dados');
    
    // Gerar arquivo
    XLSX.writeFile(wb, 'exportacao_' + new Date().toISOString().split('T')[0] + '.xlsx');
}

// Função para habilitar botões de exportação
function enableExportButtons() {
    const btnExportCSV = document.getElementById('btnExportCSV');
    const btnExportXLS = document.getElementById('btnExportXLS');
    
    if (btnExportCSV) btnExportCSV.disabled = false;
    if (btnExportXLS) btnExportXLS.disabled = false;
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Botão Executar Consulta
    const executeQueryBtn = document.getElementById('execute-query');
    if (executeQueryBtn) {
        executeQueryBtn.addEventListener('click', executeQuery);
    }
    
    // Botões de Exportação
    const exportCSVBtn = document.getElementById('btnExportCSV');
    if (exportCSVBtn) {
        exportCSVBtn.addEventListener('click', exportToCSV);
    }
    
    const exportXLSBtn = document.getElementById('btnExportXLS');
    if (exportXLSBtn) {
        exportXLSBtn.addEventListener('click', exportToXLS);
    }
});
