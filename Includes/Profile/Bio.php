<?php

if($ProfileUser['Bio']){
    echo "
    <div class='BioSection'>
        <div class='BioHeader'>
            <span class='BioHeaderIcon'>
                <svg width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M12 20h9'/><path d='M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z'/></svg>
            </span>
            <h2>Bio</h2>
        </div>
        <p class='BioText'>" . nl2br(htmlspecialchars($ProfileUser['Bio'])) . "</p>
    </div>";
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