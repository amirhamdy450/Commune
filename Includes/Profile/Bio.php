<?php

if($ProfileUser['Bio']){

    echo 

    "
        <div class='BioSection'>
            <p>" . nl2br(htmlspecialchars($ProfileUser['Bio'])) . "</p>
        </div>
    ";
}else{
    echo 
    "
        <div class='BioSection'>
            <p style='font-style:italic; color:gray'>No bio yet</p>
        </div>
    ";
}


?>