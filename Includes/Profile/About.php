<?php


$Gender= $ProfileUser['Gender'] == 0 ? 'Male' : 'Female';


/* get country name from id */
$sql = "SELECT name FROM countries WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$ProfileUser['CountryID']]);
$Country = $stmt->fetch(PDO::FETCH_ASSOC);

echo '
    <div class="AboutSection">
        <div class="AboutHeader">
            <span class="AboutHeaderIcon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            </span>
            <h2>About ' . htmlspecialchars($ProfileUser['Fname'] . ' ' . $ProfileUser['Lname']) . '</h2>
        </div>
        <div class="AboutGrid">
            <div class="AboutItem">
                <div class="AboutIconWrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="AboutItemText">
                    <span class="AboutLabel">Gender</span>
                    <span class="AboutValue">' . $Gender . '</span>
                </div>
            </div>
            <div class="AboutItem">
                <div class="AboutIconWrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div class="AboutItemText">
                    <span class="AboutLabel">Birthday</span>
                    <span class="AboutValue">' . htmlspecialchars($ProfileUser['BirthDay'] ?? 'Not specified') . '</span>
                </div>
            </div>
            <div class="AboutItem">
                <div class="AboutIconWrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </div>
                <div class="AboutItemText">
                    <span class="AboutLabel">Country</span>
                    <span class="AboutValue">' . htmlspecialchars($Country['name'] ?? 'Not specified') . '</span>
                </div>
            </div>
        </div>
    </div>
';



?>