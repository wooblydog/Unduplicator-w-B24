<?php

function pageDebug($die = true, ...$vars)
    {
        $debugType = $die ? "dd" : "dump";
        $color = $die ? "#23ff00" : "#ffee00ff";
        echo "<style> 
            .{$debugType} { 
                background-color: #000000; color:{$color}; 
                border: 1px solid #000000; padding: 10px; 
                margin: 10px; border-radius: 5px; 
                margin-bottom: 50px; 
            }
            </style>";
        foreach ($vars as $var) {
            echo "<div class=\"{$debugType}\"><pre>";
            var_export($var);
            echo "</pre></div>";
        }
        if ($die) die();
    }

    function dd(...$vars)
    {
        pageDebug(true, ...$vars);
    }

    function dump(...$vars)
    {
        pageDebug(false, ...$vars);
    }

