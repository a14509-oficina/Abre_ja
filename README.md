# Abre_ja

#exucutar
php -S localhost:8080
aGORA nao aparece para o admin editar os carros dos clientes

Testar o OCR sem câmera
Pode testar a leitura de matrículas com uma imagem estática em vez da câmera. Tire uma foto a uma matrícula com o telemóvel, copie para o Raspberry e teste assim:
pythonimport cv2
import pytesseract
import re

def limpar_matricula(texto):
    texto = texto.upper().replace(" ", "").replace("\n", "")
    match = re.search(r'[A-Z0-9]{2}-?[A-Z0-9]{2}-?[A-Z0-9]{2}', texto)
    if match:
        m = match.group().replace("-", "")
        return f"{m[0:2]}-{m[2:4]}-{m[4:6]}"
    return ""

# Testar com imagem
imagem = cv2.imread("matricula.jpg")
cinza = cv2.cvtColor(imagem, cv2.COLOR_BGR2GRAY)
texto = pytesseract.image_to_string(cinza)
print("Lido:", limpar_matricula(texto))
3. Testar a ligação ao Supabase
bashcurl "https://fmjytigqgpfocurpjvtv.supabase.co/rest/v1/cars?select=plate" \
  -H "apikey: SUA_CHAVE"




  <?php
require_once 'includes/db.php';
require_once 'includes/helpers.php';
session_start();

// Verifica se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Utilizador';
$isAdmin = $_SESSION['is_admin'] ?? false;

// 1. Procurar portões no Supabase
$portoes = supabase('portoes?select=*', 'GET');

// 2. Se for admin, carregar logs e utilizadores para os filtros
$logs = [];
$utilizadores = [];
if ($isAdmin) {
    $search = $_GET['search'] ?? '';
    $userEndpoint = $search ? "users?email=ilike.*$search*" : "users?select=*";
    $utilizadores = supabase($userEndpoint, 'GET');
    $logs = supabase('access_logs?select=*&order=created_at.desc&limit=10', 'GET');
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Painel de Controlo - Portões</title>
    <link rel="stylesheet" href="css/style.css"> <style>
        /* Ajustes visuais sugeridos: Tirar cor dos portões */
        .gate-card {
            border: 1px solid #ddd;
            background: #fff;
            padding: 20px;
            margin: 10px;
            border-radius: 12px;
            display: inline-block;
            width: 250px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .btn-open {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-open:hover { background-color: #218838; }
        .admin-section { margin-top: 40px; border-top: 2px solid #eee; padding-top: 20px; }
        .search-box { margin-bottom: 20px; }
    </style>
</head>
<body>

    <h1>Bem-vindo, <?php echo htmlspecialchars($userName); ?></h1>

    <div class="dashboard">
        <h2>Meus Portões</h2>
        <div class="gates-container">
            <?php foreach ($portoes as $gate): ?>
                <div class="gate-card">
                    <h3><?php echo htmlspecialchars($gate['nome']); ?></h3>
                    <button onclick="abrirPortao('<?php echo $gate['nome']; ?>')" class="btn-open">
                        Abrir Portão
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <div class="admin-section">
        <h2>Painel Admin</h2>

        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" placeholder="Pesquisar utilizador (email)..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <button type="submit">Filtrar</button>
            </form>
        </div>

        <h3>Utilizadores</h3>
        <ul>
            <?php foreach ($utilizadores as $u): ?>
                <li>
                    <?php echo htmlspecialchars($u['email']); ?> 
                    - <?php echo $u['email_verified'] ? '✅ Verificado' : '❌ Pendente'; ?>
                    <button onclick="apagarConta('<?php echo $u['id']; ?>')">Apagar</button>
                </li>
            <?php endforeach; ?>
        </ul>

        <h3>Últimos Acessos (Histórico)</h3>
        <table border="1" width="100%">
            <tr>
                <th>Utilizador</th>
                <th>Portão</th>
                <th>Data/Hora</th>
            </tr>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                <td><?php echo htmlspecialchars($log['gate_name']); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    <script>
    function abrirPortao(nomePortao) {
        if(!confirm('Deseja abrir o ' + nomePortao + '?')) return;

        fetch('abrir_portao.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ gate_name: nomePortao })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) alert(data.success);
            else alert('Erro: ' + data.error);
            location.reload();
        });
    }

    function apagarConta(id) {
        if(confirm('Tem a certeza que quer apagar esta conta?')) {
            // Lógica para chamar supabase DELETE via uma action
            window.location.href = 'actions/delete_user.php?id=' + id;
        }
    }
    </script>
</body>
</html>