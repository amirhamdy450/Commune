<?php

if (!function_exists('RenderEmptyState')) {
    function RenderEmptyState(string $IconPath, string $Title, string $Description, string $ClassName = 'ProfileEmptyState'): void
    {
        echo '<div class="' . htmlspecialchars($ClassName) . '">
            <img src="' . htmlspecialchars($IconPath) . '" alt="">
            <h3>' . htmlspecialchars($Title) . '</h3>
            <p>' . htmlspecialchars($Description) . '</p>
        </div>';
    }
}
