<?php
require_once "../config/conexao.php";

$nome = "Administrador";
$email = "admin@directos.com";
$senha = "123456";

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$sqlVerifica = "
    SELECT COUNT(*) 
    FROM OS_Usuarios 
    WHERE Email = :Email
";

$stmtVerifica = $conn->prepare($sqlVerifica);
$stmtVerifica->bindValue(":Email", $email);
$stmtVerifica->execute();

$existe = $stmtVerifica->fetchColumn();

if ($existe > 0) {
    die("Usuário administrador já existe.");
}

$sql = "
    INSERT INTO OS_Usuarios
    (
        Nome,
        Email,
        SenhaHash,
        Perfil,
        Ativo
    )
    VALUES
    (
        :Nome,
        :Email,
        :SenhaHash,
        :Perfil,
        :Ativo
    )
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":Nome", $nome);
$stmt->bindValue(":Email", $email);
$stmt->bindValue(":SenhaHash", $senhaHash);
$stmt->bindValue(":Perfil", "Admin");
$stmt->bindValue(":Ativo", 1, PDO::PARAM_INT);
$stmt->execute();

echo "Usuário administrador criado com sucesso!<br>";
echo "Email: " . $email . "<br>";
echo "Senha: " . $senha . "<br>";