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
            label: checkbox.getAttribute('data-label')
        });
    });
    
    return selectedColumns;
}

// Função para gerar o SQL
function generateSQL() {
    const selectedColumns = getSelectedColumns();
    if (selectedColumns.length === 0) return null;

    const filters = getFilters();
    
    // Tabelas necessárias baseadas nas colunas selecionadas
    const tables = new Set();
    selectedColumns.forEach(col => {
        if (col.key.startsWith('prd_')) tables.add('s_prd');
        if (col.key.startsWith('re_')) tables.add('prestador');
        if (col.key === 'descricao' || col.key === 'prd_pa') tables.add('procedimento');
    });

    // Construir a consulta SQL
    let sql = 'SELECT ';
    
    // Adicionar colunas selecionadas
    const columnsSql = selectedColumns.map(col => {
        // Tratar campos totalizáveis
        if (['prd_qt_a', 'prd_qt_p', 'prd_vl_p'].includes(col.key)) {
            return `SUM(${col.key}) as ${col.key}`;
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
    
    // GROUP BY para campos totalizáveis
    const nonAggregateColumns = selectedColumns.filter(col => 
        !['prd_qt_a', 'prd_qt_p', 'prd_vl_p'].includes(col.key)
    );
    
    if (nonAggregateColumns.length > 0) {
        sql += ' GROUP BY ' + nonAggregateColumns.map(col => col.key).join(', ');
    }
    
    return sql;
}

// Função para executar a consulta
async function executeQuery() {
    try {
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
        updatePagination();
        
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
    }
}

// Função para atualizar a tabela
function updateTable(data) {
    const tableContainer = document.getElementById('results-container');
    const thead = document.getElementById('results-header');
    const tbody = document.getElementById('results-table');
    
    if (!thead || !tbody) {
        console.error('Elementos da tabela não encontrados:', { thead, tbody });
        return;
    }
    
    // Limpar tabela atual
    thead.innerHTML = '';
    tbody.innerHTML = '';
    
    if (!data || data.length === 0) {
        console.log('Nenhum dado para exibir');
        tableContainer.style.display = 'none';
        return;
    }
    
    // Mostrar a tabela
    tableContainer.style.display = 'block';
    
    // Criar cabeçalho
    const headerRow = document.createElement('tr');
    const selectedColumns = getSelectedColumns();
    
    selectedColumns.forEach(col => {
        const th = document.createElement('th');
        th.textContent = col.label;
        headerRow.appendChild(th);
    });
    
    if (data[0].hasOwnProperty('ativo')) {
        const th = document.createElement('th');
        th.textContent = 'Ativo';
        headerRow.appendChild(th);
    }
    
    thead.appendChild(headerRow);
    
    // Criar linhas de dados
    data.forEach(row => {
        const tr = document.createElement('tr');
        if (row.hasOwnProperty('ativo') && !row.ativo) {
            tr.classList.add('prestador-inativo');
        }
        selectedColumns.forEach(col => {
            const td = document.createElement('td');
            let value = row[col.key];
            
            // Formatar números
            if (['prd_qt_a', 'prd_qt_p', 'prd_vl_p'].includes(col.key)) {
                value = formatNumber(value);
            }
            
            td.textContent = value || '';
            tr.appendChild(td);
        });
        if (row.hasOwnProperty('ativo')) {
            const td = document.createElement('td');
            td.textContent = row.ativo ? 'Sim' : 'Não';
            tr.appendChild(td);
        }
        tbody.appendChild(tr);
    });
}

// Função para atualizar a paginação
function updatePagination() {
    const pagination = document.getElementById('pagination');
    if (!pagination) return;
    
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    
    // Limpar paginação atual
    pagination.innerHTML = '';
    
    // Criar elementos de paginação
    const ul = document.createElement('ul');
    ul.className = 'pagination justify-content-center';
    
    // Botão anterior
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    const prevLink = document.createElement('a');
    prevLink.className = 'page-link';
    prevLink.href = '#';
    prevLink.textContent = 'Anterior';
    prevLink.onclick = (e) => {
        e.preventDefault();
        if (currentPage > 1) {
            currentPage--;
            executeQuery();
        }
    };
    prevLi.appendChild(prevLink);
    ul.appendChild(prevLi);
    
    // Páginas
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        const link = document.createElement('a');
        link.className = 'page-link';
        link.href = '#';
        link.textContent = i;
        link.onclick = (e) => {
            e.preventDefault();
            currentPage = i;
            executeQuery();
        };
        li.appendChild(link);
        ul.appendChild(li);
    }
    
    // Botão próximo
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    const nextLink = document.createElement('a');
    nextLink.className = 'page-link';
    nextLink.href = '#';
    nextLink.textContent = 'Próximo';
    nextLink.onclick = (e) => {
        e.preventDefault();
        if (currentPage < totalPages) {
            currentPage++;
            executeQuery();
        }
    };
    nextLi.appendChild(nextLink);
    ul.appendChild(nextLi);
    
    pagination.appendChild(ul);
}

// Função para formatar números
function formatNumber(value) {
    if (value === null || value === undefined) return '';
    
    const num = parseFloat(value);
    if (isNaN(num)) return value;
    
    return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num);
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
