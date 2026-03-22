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

    function tgDebug($pretty = true, ...$vars): void
    {
        $json = json_encode($vars, JSON_UNESCAPED_UNICODE | ($pretty ? JSON_PRETTY_PRINT : 0));
        $text = "<pre>" . htmlspecialchars($json, ENT_QUOTES) . "</pre>";

        $params = [
            'chat_id' => -1002115190876,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];
        $ch = curl_init('https://api.telegram.org/bot7055483414:AAE6Ck2F8fRBZ0baNEXuhg677fjwlY0S7ME/sendMessage');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

