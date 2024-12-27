// Estrutura das colunas disponíveis
document.addEventListener('DOMContentLoaded', function() {
    // Array com as colunas disponíveis
    const availableColumns = [
        { table: 's_prd', column: 'prd_uid', label: 'ID do Prestador' },
        { table: 's_prd', column: 'prd_pa', label: 'Código do Procedimento' },
        { table: 's_prd', column: 'prd_cbo', label: 'CBO' },
        { table: 's_prd', column: 'prd_dt_atend', label: 'Data de Atendimento' },
        { table: 's_prd', column: 'prd_qtd_apres', label: 'Quantidade Apresentada', summable: true },
        { table: 's_prd', column: 'prd_vl_apres', label: 'Valor Apresentado', summable: true },
        { table: 's_prd', column: 'prd_qtd_aprov', label: 'Quantidade Aprovada', summable: true },
        { table: 's_prd', column: 'prd_vl_aprov', label: 'Valor Aprovado', summable: true },
        { table: 'prestador', column: 're_nome', label: 'Nome do Prestador' },
        { table: 'prestador', column: 're_cpf_cgc', label: 'CPF/CNPJ' },
        { table: 'procedimento', column: 'nome', label: 'Nome do Procedimento' },
        { table: 'procedimento', column: 'codigo', label: 'Código do Procedimento' },
        { table: 'cbo', column: 'DESCRICAO', label: 'Descrição do CBO' }
    ];

    // Variáveis globais
    let currentPage = 1;
    const itemsPerPage = 50;
    let totalItems = 0;
    let currentData = [];

    // Função para salvar a consulta
    function saveQuery(sql) {
        if (!sql) return;
        
        // Armazenar o SQL gerado globalmente
        window.lastGeneratedSQL = sql;
        
        // Atualizar a visualização do SQL
        updateSQLPreview(sql);
        
        console.log('SQL salvo:', sql); // Debug
    }

    // Função para inicializar a seleção de colunas
    function initializeColumnSelection() {
        const columnSelection = document.getElementById('column-selection');
        if (!columnSelection) {
            console.error('Elemento column-selection não encontrado');
            return;
        }

        availableColumns.forEach(col => {
            const div = document.createElement('div');
            div.className = 'form-check';
            
            const input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'form-check-input';
            input.id = `col-${col.table}-${col.column}`;
            input.dataset.table = col.table;
            input.dataset.column = col.column;
            
            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = `col-${col.table}-${col.column}`;
            label.textContent = col.label;
            
            div.appendChild(input);
            div.appendChild(label);
            columnSelection.appendChild(div);
        });
    }

    // Configuração dos operadores por tipo de campo
    const operatorsByType = {
        text: [
            { value: 'LIKE', label: 'Contém' },
            { value: 'NOT LIKE', label: 'Não Contém' },
            { value: '=', label: 'Igual a' },
            { value: '<>', label: 'Diferente de' }
        ],
        number: [
            { value: '=', label: 'Igual a' },
            { value: '<>', label: 'Diferente de' },
            { value: '>', label: 'Maior que' },
            { value: '>=', label: 'Maior ou igual a' },
            { value: '<', label: 'Menor que' },
            { value: '<=', label: 'Menor ou igual a' }
        ],
        date: [
            { value: '=', label: 'Igual a' },
            { value: '<>', label: 'Diferente de' },
            { value: '>', label: 'Após' },
            { value: '>=', label: 'A partir de' },
            { value: '<', label: 'Antes de' },
            { value: '<=', label: 'Até' }
        ]
    };

    // Mapeamento de campos para seus tipos
    const fieldTypes = {
        // Campos tipo texto
        'prd_uid': 'text',
        're_cnome': 'text',
        'prd_pa': 'text',
        'procedimento': 'text',
        'prd_cbo': 'text',
        'descricao': 'text',
        'rub_dc': 'text',
        'financiamento': 'text',
        'relatorio': 'text',
        
        // Campos tipo número
        'prd_idade': 'number',
        'prd_qt_a': 'number',
        'prd_qt_p': 'number',
        'prd_vl_p': 'number',
        
        // Campos tipo data
        'prd_mvm': 'date'
    };

    // Mapeamento de rótulos para campos
    const fieldLabels = {
        'prd_uid': 'CNES',
        're_cnome': 'Prestador',
        'prd_pa': 'Procedimento',
        'procedimento': 'Descrição',
        'prd_mvm': 'Competência',
        'prd_cbo': 'CBO',
        'descricao': 'Profissional',
        'prd_idade': 'Idade',
        'prd_qt_a': 'Quantidade Apresentada',
        'prd_qt_p': 'Quantidade Aprovada',
        'prd_vl_p': 'Valor Aprovado',
        'rub_dc': 'Rubrica',
        'financiamento': 'Financiamento',
        'relatorio': 'Relatório Quadrimestral'
    };

    // Categorias de campos
    const fieldCategories = {
        'Identificação': ['prd_uid', 're_cnome', 'prd_pa', 'procedimento', 'prd_mvm', 'prd_cbo', 'descricao', 'prd_idade'],
        'Valores': ['prd_qt_a', 'prd_qt_p', 'prd_vl_p'],
        'Classificação': ['rub_dc', 'financiamento', 'relatorio']
    };

    function addFilter() {
        const container = document.getElementById('filters-container');
        const filterDiv = document.createElement('div');
        filterDiv.className = 'filter-container mb-3';
        
        // Criar o conteúdo do filtro
        const filterContent = document.createElement('div');
        filterContent.className = 'row g-2 align-items-center';
        
        // Coluna para seleção do campo
        const fieldCol = document.createElement('div');
        fieldCol.className = 'col-md-4';
        const fieldSelect = document.createElement('select');
        fieldSelect.className = 'form-select filter-field';
        fieldSelect.innerHTML = '<option value="">Selecione o campo</option>';
        
        // Adicionar opções agrupadas por categoria
        for (const [category, fields] of Object.entries(fieldCategories)) {
            const group = document.createElement('optgroup');
            group.label = category;
            
            fields.forEach(field => {
                const option = document.createElement('option');
                option.value = field;
                option.textContent = fieldLabels[field];
                group.appendChild(option);
            });
            
            fieldSelect.appendChild(group);
        }
        
        // Event listener para atualizar operadores quando o campo mudar
        fieldSelect.addEventListener('change', function() {
            const fieldType = fieldTypes[this.value] || 'text';
            const operators = operatorsByType[fieldType];
            const operatorSelect = this.closest('.filter-container').querySelector('.filter-operator');
            
            operatorSelect.innerHTML = operators.map(op => 
                `<option value="${op.value}">${op.label}</option>`
            ).join('');
            
            // Atualizar o tipo do input de valor
            const valueInput = this.closest('.filter-container').querySelector('.filter-value');
            if (fieldType === 'date') {
                valueInput.type = 'month';
                valueInput.pattern = '[0-9]{6}';
            } else if (fieldType === 'number') {
                valueInput.type = 'number';
                valueInput.step = this.value === 'prd_vl_p' ? '0.01' : '1';
            } else {
                valueInput.type = 'text';
            }
        });
        
        fieldCol.appendChild(fieldSelect);
        
        // Coluna para seleção do operador
        const operatorCol = document.createElement('div');
        operatorCol.className = 'col-md-3';
        const operatorSelect = document.createElement('select');
        operatorSelect.className = 'form-select filter-operator';
        operatorSelect.innerHTML = operatorsByType.text.map(op => 
            `<option value="${op.value}">${op.label}</option>`
        ).join('');
        operatorCol.appendChild(operatorSelect);
        
        // Coluna para o valor
        const valueCol = document.createElement('div');
        valueCol.className = 'col-md-4';
        const valueInput = document.createElement('input');
        valueInput.type = 'text';
        valueInput.className = 'form-control filter-value';
        valueInput.placeholder = 'Valor';
        valueCol.appendChild(valueInput);
        
        // Coluna para o botão de remover
        const removeCol = document.createElement('div');
        removeCol.className = 'col-md-1';
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn btn-danger btn-sm';
        removeButton.innerHTML = '<i class="fas fa-times"></i>';
        removeButton.onclick = function() {
            this.closest('.filter-container').remove();
        };
        removeCol.appendChild(removeButton);
        
        // Adicionar todas as colunas ao container
        filterContent.appendChild(fieldCol);
        filterContent.appendChild(operatorCol);
        filterContent.appendChild(valueCol);
        filterContent.appendChild(removeCol);
        
        filterDiv.appendChild(filterContent);
        container.appendChild(filterDiv);
    }

    // Funções de exportação
    function exportToCSV() {
        if (!currentData || !currentData.length) return;
        
        const selectedColumns = getSelectedColumns();
        const csvContent = [
            selectedColumns.map(col => col.label).join(','),
            ...currentData.map(row => 
                selectedColumns.map(col => `"${(row[col.key] || '').toString().replace(/"/g, '""')}"`).join(',')
            )
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'producao_' + new Date().toISOString().split('T')[0] + '.csv';
        link.click();
    }

    function exportToXLS() {
        if (!currentData || !currentData.length) return;
        
        const selectedColumns = getSelectedColumns();
        const ws = XLSX.utils.json_to_sheet(
            currentData.map(row => {
                const newRow = {};
                selectedColumns.forEach(col => {
                    newRow[col.label] = row[col.key] || '';
                });
                return newRow;
            })
        );
        
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Produção');
        XLSX.writeFile(wb, 'producao_' + new Date().toISOString().split('T')[0] + '.xlsx');
    }

    // Função para obter filtros
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

    // Função para executar consulta
    async function executeQuery() {
        const selectedColumns = getSelectedColumns();
        if (selectedColumns.length === 0) {
            alert('Por favor, selecione pelo menos uma coluna para consultar.');
            return;
        }

        const filters = getFilters();
        const queryData = {
            columns: selectedColumns,
            filters: filters,
            page: currentPage,
            itemsPerPage: itemsPerPage
        };

        try {
            const response = await fetch('actions/execute_query.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(queryData)
            });

            if (!response.ok) {
                throw new Error('Erro na consulta');
            }

            const data = await response.json();
            currentData = data.results;
            totalItems = data.total;

            updateTable(data.results);
            updatePagination();
            updateSQLPreview(data.sql);
            enableExportButtons();
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao executar a consulta: ' + error.message);
        }
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

    // Função para gerar o SQL
    function generateSQL() {
        const selectedColumns = [];
        const selectedTables = new Set(['s_prd']); // Tabela base

        // Coletar colunas selecionadas
        const checkedColumns = document.querySelectorAll('.form-check-input:checked');
        if (checkedColumns.length === 0) {
            alert('Selecione pelo menos uma coluna');
            return null;
        }

        checkedColumns.forEach(checkbox => {
            const table = checkbox.dataset.table;
            const column = checkbox.dataset.column;
            selectedColumns.push(`${table}.${column}`);
            selectedTables.add(table);
        });

        // Gerar JOINs necessários
        const joins = [];
        if (selectedTables.has('prestador')) {
            joins.push('LEFT JOIN prestador ON s_prd.prd_uid = prestador.re_cunid');
        }
        if (selectedTables.has('procedimento')) {
            joins.push('LEFT JOIN procedimento ON s_prd.prd_pa = procedimento.codigo');
        }
        if (selectedTables.has('cbo')) {
            joins.push('LEFT JOIN cbo ON s_prd.prd_cbo = cbo.CODIGO');
        }

        // Construir cláusula WHERE com os filtros
        const whereClauses = [];
        const filterRows = document.querySelectorAll('.filter-container');
        
        filterRows.forEach(filter => {
            const columnSelect = filter.querySelector('.filter-field');
            const operatorSelect = filter.querySelector('.filter-operator');
            const valueInput = filter.querySelector('.filter-value');

            if (columnSelect && operatorSelect && valueInput && 
                columnSelect.value && operatorSelect.value && valueInput.value.trim()) {
                
                const value = valueInput.value.trim();
                
                if (operatorSelect.value === 'LIKE' || operatorSelect.value === 'NOT LIKE') {
                    whereClauses.push(`${columnSelect.value} ${operatorSelect.value} '%${value}%'`);
                } else if (operatorSelect.value === 'IN' || operatorSelect.value === 'NOT IN') {
                    const values = value.split(',').map(v => `'${v.trim()}'`).join(',');
                    whereClauses.push(`${columnSelect.value} ${operatorSelect.value} (${values})`);
                } else {
                    whereClauses.push(`${columnSelect.value} ${operatorSelect.value} '${value}'`);
                }
            }
        });

        // Construir SQL completo
        let sql = `SELECT ${selectedColumns.join(', ')} FROM s_prd`;
        
        if (joins.length > 0) {
            sql += ' ' + joins.join(' ');
        }
        
        if (whereClauses.length > 0) {
            sql += ' WHERE ' + whereClauses.join(' AND ');
        }

        return sql;
    }

    // Função para executar a consulta
    async function executeQuery() {
        const selectedColumns = getSelectedColumns();
        if (selectedColumns.length === 0) {
            alert('Por favor, selecione pelo menos uma coluna para consultar.');
            return;
        }

        const filters = getFilters();
        const queryData = {
            columns: selectedColumns,
            filters: filters,
            page: currentPage,
            itemsPerPage: itemsPerPage
        };

        try {
            const response = await fetch('actions/execute_query.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(queryData)
            });

            if (!response.ok) {
                throw new Error('Erro na consulta');
            }

            const data = await response.json();
            currentData = data.results;
            totalItems = data.total;

            updateTable(data.results);
            updatePagination();
            updateSQLPreview(data.sql);
            enableExportButtons();
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao executar a consulta: ' + error.message);
        }
    }

    function updateTable(data) {
        const thead = document.getElementById('results-header');
        const tbody = document.getElementById('results-table');
        
        // Limpa a tabela
        thead.innerHTML = '';
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="100%" class="text-center">Nenhum resultado encontrado</td></tr>';
            return;
        }

        // Adiciona cabeçalhos
        const headerRow = document.createElement('tr');
        const selectedColumns = getSelectedColumns();
        selectedColumns.forEach(column => {
            const th = document.createElement('th');
            th.textContent = column.label;
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);

        // Adiciona dados
        data.forEach(row => {
            const tr = document.createElement('tr');
            selectedColumns.forEach(column => {
                const td = document.createElement('td');
                td.textContent = row[column.key] || '';
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
    }

    // Função para atualizar a paginação
    function updatePagination() {
        const pagination = document.getElementById('pagination');
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        
        pagination.innerHTML = '';
        
        if (totalPages <= 1) return;

        const ul = document.createElement('ul');
        ul.className = 'pagination';

        // Botão Anterior
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage - 1}">Anterior</a>`;
        ul.appendChild(prevLi);

        // Páginas
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
                ul.appendChild(li);
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                const li = document.createElement('li');
                li.className = 'page-item disabled';
                li.innerHTML = '<span class="page-link">...</span>';
                ul.appendChild(li);
            }
        }

        // Botão Próximo
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage + 1}">Próximo</a>`;
        ul.appendChild(nextLi);

        pagination.appendChild(ul);

        // Adiciona event listeners para os links de paginação
        ul.querySelectorAll('a.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const newPage = parseInt(this.dataset.page);
                if (newPage !== currentPage && newPage > 0 && newPage <= totalPages) {
                    currentPage = newPage;
                    executeQuery();
                }
            });
        });
    }

    // Função para atualizar a visualização do SQL
    function updateSQLPreview(sql) {
        const sqlPreview = document.getElementById('sql-preview');
        sqlPreview.textContent = sql;
    }

    // Função para habilitar os botões de exportação
    function enableExportButtons() {
        document.getElementById('btnExportCSV').disabled = false;
        document.getElementById('btnExportXLS').disabled = false;
    }

    function exportToCSV() {
        if (!currentData.length) return;
        
        const selectedColumns = getSelectedColumns();
        const csvContent = [
            selectedColumns.map(col => col.label).join(','),
            ...currentData.map(row => 
                selectedColumns.map(col => `"${(row[col.key] || '').toString().replace(/"/g, '""')}"`).join(',')
            )
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'producao_' + new Date().toISOString().split('T')[0] + '.csv';
        link.click();
    }

    function exportToXLS() {
        if (!currentData.length) return;
        
        const selectedColumns = getSelectedColumns();
        const ws = XLSX.utils.json_to_sheet(
            currentData.map(row => {
                const newRow = {};
                selectedColumns.forEach(col => {
                    newRow[col.label] = row[col.key] || '';
                });
                return newRow;
            })
        );
        
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Produção');
        XLSX.writeFile(wb, 'producao_' + new Date().toISOString().split('T')[0] + '.xlsx');
    }

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

    function getSelectedColumns() {
        const selectedColumns = [];
        const checkedColumns = document.querySelectorAll('.form-check-input:checked');
        
        checkedColumns.forEach(checkbox => {
            const table = checkbox.dataset.table;
            const column = checkbox.dataset.column;
            selectedColumns.push({ key: `${table}.${column}`, label: checkbox.nextElementSibling.textContent });
        });
        
        return selectedColumns;
    }

    function formatNumber(value) {
        const num = parseFloat(value);
        return isNaN(num) ? value : new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num);
    }

    function updateExportButtons(hasData) {
        const btnExportCSV = document.getElementById('btnExportCSV');
        const btnExportXLS = document.getElementById('btnExportXLS');
        
        if (btnExportCSV) {
            btnExportCSV.disabled = !hasData;
        }
        if (btnExportXLS) {
            btnExportXLS.disabled = !hasData;
        }
    }

    async function exportData(format) {
        const sql = generateSQL();
        if (!sql) {
            alert('Gere uma consulta primeiro');
            return;
        }

        try {
            // Tentar usar a API moderna de clipboard primeiro
            await navigator.clipboard.writeText(sql);
            alert('SQL copiado para a área de transferência!');
        } catch (err) {
            console.error('Erro ao copiar SQL:', err);
            // Fallback para método alternativo
            const textArea = document.createElement('textarea');
            textArea.value = sql;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                alert('SQL copiado para a área de transferência!');
            } catch (err) {
                console.error('Erro ao copiar SQL (fallback):', err);
            }
            document.body.removeChild(textArea);
        }
    }

    function exportToCSV(rows) {
        let csvContent = "data:text/csv;charset=utf-8,";
        
        rows.forEach(row => {
            const processedRow = row.map(cell => {
                // Tratar células nulas ou undefined
                if (cell === null || cell === undefined) {
                    return '';
                }
                // Escapar aspas duplas e envolver em aspas se necessário
                const cellStr = String(cell).replace(/"/g, '""');
                return /[,"\n]/.test(cellStr) ? `"${cellStr}"` : cellStr;
            });
            csvContent += processedRow.join(',') + "\n";
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "exportacao_" + new Date().toISOString().slice(0,10) + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportToXLS(rows) {
        let xlsContent = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel'>";
        xlsContent += "<head><meta charset='UTF-8'></head><body>";
        xlsContent += "<table>";
        
        rows.forEach(row => {
            xlsContent += "<tr>";
            row.forEach(cell => {
                // Tratar células nulas ou undefined
                const cellValue = cell === null || cell === undefined ? '' : cell;
                xlsContent += `<td>${cellValue}</td>`;
            });
            xlsContent += "</tr>";
        });
        
        xlsContent += "</table></body></html>";

        const blob = new Blob([xlsContent], { type: 'application/vnd.ms-excel' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = "exportacao_" + new Date().toISOString().slice(0,10) + ".xls";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    }

    async function copySQLToClipboard() {
        const sqlPreview = document.getElementById('sql-preview');
        if (!sqlPreview || !sqlPreview.textContent) return;

        try {
            // Tentar usar a API moderna de clipboard primeiro
            await navigator.clipboard.writeText(sqlPreview.textContent);
            alert('SQL copiado para a área de transferência!');
        } catch (err) {
            console.error('Erro ao copiar SQL:', err);
            // Fallback para método alternativo
            const textArea = document.createElement('textarea');
            textArea.value = sqlPreview.textContent;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                alert('SQL copiado para a área de transferência!');
            } catch (err) {
                console.error('Erro ao copiar SQL (fallback):', err);
            }
            document.body.removeChild(textArea);
        }
    }

    function setupSQLControls() {
        console.log('Configurando controles SQL...'); // Debug
        
        const copyBtn = document.getElementById('copy-sql');
        const toggleBtn = document.getElementById('toggle-sql');
        const sqlPreview = document.getElementById('sql-preview');

        // Garantir que o SQL começa colapsado
        if (sqlPreview) {
            sqlPreview.classList.add('collapsed');
        }

        if (copyBtn) {
            copyBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                await copySQLToClipboard();
            });
            console.log('Botão copiar configurado'); // Debug
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Clique no botão expandir detectado'); // Debug
                
                if (!sqlPreview) {
                    console.log('Elemento sql-preview não encontrado'); // Debug
                    return;
                }

                // Toggle das classes
                sqlPreview.classList.toggle('collapsed');
                toggleBtn.classList.toggle('expanded');
                
                // Atualizar ícone e texto
                const icon = toggleBtn.querySelector('i');
                const span = toggleBtn.querySelector('span');
                
                if (sqlPreview.classList.contains('collapsed')) {
                    icon.className = 'fas fa-chevron-down';
                    span.textContent = ' Expandir SQL';
                    console.log('SQL colapsado'); // Debug
                } else {
                    icon.className = 'fas fa-chevron-up';
                    span.textContent = ' Recolher SQL';
                    console.log('SQL expandido'); // Debug
                }
            });
            console.log('Botão expandir configurado'); // Debug
        }
    }

    // Garantir que setupSQLControls é chamado após o DOM estar pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupSQLControls);
    } else {
        setupSQLControls();
    }

    function highlightSQL(sql) {
        if (!sql) return '';
        
        // Lista de palavras-chave SQL para destacar
        const keywords = ['SELECT', 'FROM', 'WHERE', 'GROUP BY', 'ORDER BY', 'LIMIT', 'JOIN', 'LEFT JOIN', 'AND', 'OR', 'AS', 'ON', 'SUM'];
        
        // Substituir palavras-chave por versões destacadas
        let highlightedSQL = sql;
        keywords.forEach(keyword => {
            const regex = new RegExp(`\\b${keyword}\\b`, 'gi');
            highlightedSQL = highlightedSQL.replace(regex, match => `<span class="keyword">${match}</span>`);
        });

        // Destacar funções
        highlightedSQL = highlightedSQL.replace(/\b\w+\(/g, match => `<span class="function">${match}</span>`);

        // Destacar strings (entre aspas simples)
        highlightedSQL = highlightedSQL.replace(/'[^']*'/g, match => `<span class="string">${match}</span>`);

        // Destacar números
        highlightedSQL = highlightedSQL.replace(/\b\d+(\.\d+)?\b/g, match => `<span class="number">${match}</span>`);

        return highlightedSQL;
    }

    function updateSQLPreview(sql) {
        const sqlPreview = document.getElementById('sql-preview');
        if (sqlPreview) {
            sqlPreview.innerHTML = highlightSQL(sql);
        }
    }
});
