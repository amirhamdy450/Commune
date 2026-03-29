<?php


$Gender= $ProfileUser['Gender'] == 0 ? 'Male' : 'Female';


/* get country name from id */
$sql = "SELECT name FROM countries WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$ProfileUser['CountryID']]);
$Country = $stmt->fetch(PDO::FETCH_ASSOC);

echo '
    <div class="AboutSection">
        <h2>About '. $ProfileUser['Fname'] . ' ' . $ProfileUser['Lname'] . '</h2>
        <p><strong>Gender:</strong> '.$Gender.'</p>
        <p><strong>Birthday:</strong> '.$ProfileUser['BirthDay'].'</p>
        <p><strong>Country:</strong> '.htmlspecialchars($Country['name'] ?? 'Not specified').'</p>
    </div>

';



?>