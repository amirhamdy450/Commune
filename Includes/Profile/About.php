<?php


$Gender= $ProfileUser['Gender'] == 0 ? 'Male' : 'Female';


/* get country name from id */
$sql = "SELECT name FROM countries WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$ProfileUser['CountryID']]);
$Country = $stmt->fetch(PDO::FETCH_ASSOC);

echo '
    <div class="AboutSection">
        <h2>About ' . htmlspecialchars($ProfileUser['Fname'] . ' ' . $ProfileUser['Lname']) . '</h2>
        <div class="AboutGrid">
            <div class="AboutItem">
                <span class="AboutLabel">Gender</span>
                <span class="AboutValue">' . $Gender . '</span>
            </div>
            <div class="AboutItem">
                <span class="AboutLabel">Birthday</span>
                <span class="AboutValue">' . htmlspecialchars($ProfileUser['BirthDay'] ?? 'Not specified') . '</span>
            </div>
            <div class="AboutItem">
                <span class="AboutLabel">Country</span>
                <span class="AboutValue">' . htmlspecialchars($Country['name'] ?? 'Not specified') . '</span>
            </div>
        </div>
    </div>
';



?>