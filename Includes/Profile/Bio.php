<?php

if($ProfileUser['Bio']){
    echo "<div class='BioSection'><p>" . nl2br(htmlspecialchars($ProfileUser['Bio'])) . "</p></div>";
}else{
    echo "
        <div class='ProfileEmptyState'>
            <img src='Imgs/Icons/no-bio.svg' alt=''>
            <h3>No bio yet</h3>
            <p>This user hasn't written anything about themselves.</p>
        </div>
    ";
}


?>