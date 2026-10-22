<?php
/*
 * Generates original SVG cover artwork for a game.
 *
 * We do not use copyrighted box art. Each cover is drawn from simple shapes:
 * the game's primary genre picks the motif (mountains, hex grid, crosshair...)
 * and the game id seeds the colours and positions, so every cover is unique
 * but always the same for the same game.
 */

function cover_svg(int $id, string $genre): string
{
    // Small deterministic random generator seeded by the game id.
    $seed = ($id * 7919 + 104729) % 2147483647;
    $rand = function (float $min = 0, float $max = 1) use (&$seed): float {
        $seed = ($seed * 48271) % 2147483647;
        return $min + ($seed / 2147483647) * ($max - $min);
    };

    $baseHues = [
        'action' => 12, 'adventure' => 168, 'rpg' => 262, 'platformer' => 205, 'puzzle' => 286,
        'strategy' => 38, 'simulation' => 128, 'shooter' => 352, 'roguelike' => 322, 'horror' => 228,
        'racing' => 26, 'sports' => 188, 'fighting' => 340, 'party' => 48, 'survival' => 182,
    ];
    $h = (int) round(($baseHues[$genre] ?? 250) + $rand(-40, 40) + 360) % 360;
    $h2 = ($h + 32) % 360;
    $c = fn(int $hue, int $s, int $l, float $a = 1) => "hsla($hue,$s%,$l%,$a)";
    $uid = 'c' . $id;

    $art = '';
    switch ($genre) {
        case 'adventure':
            $sx = $rand(70, 230);
            $art .= sprintf('<circle cx="%.0f" cy="115" r="42" fill="%s"/>', $sx, $c($h2, 90, 78, .85));
            foreach ([[170, 40, .55], [215, 30, .75], [255, 22, 1]] as $i => [$base, $l, $a]) {
                $pts = "0,400 0,$base";
                for ($x = 0; $x <= 300; $x += 50) {
                    $pts .= sprintf(' %d,%.0f', $x + 25, $base - $rand(20, 70) + $i * 6);
                    $pts .= sprintf(' %d,%.0f', $x + 50, $base + $rand(-5, 10));
                }
                $pts .= ' 300,400';
                $art .= sprintf('<polygon points="%s" fill="%s"/>', $pts, $c($h, 35, $l, $a));
            }
            break;

        case 'platformer':
            $art .= sprintf('<circle cx="%.0f" cy="70" r="26" fill="%s"/>', $rand(60, 240), $c($h2, 80, 85, .9));
            for ($i = 0; $i < 6; $i++) {
                $w = $rand(50, 110);
                $x = $rand(-10, 300 - $w);
                $y = 120 + $i * 38 + $rand(-8, 8);
                $art .= sprintf('<rect x="%.0f" y="%.0f" width="%.0f" height="12" rx="4" fill="%s"/>', $x, $y, $w, $c($h2, 70, 70, .55 + $i * .06));
                if ($i % 2 === 0) {
                    $art .= sprintf('<rect x="%.0f" y="%.0f" width="14" height="14" rx="2" fill="%s"/>', $x + $w / 2, $y - 16, $c($h, 90, 80, .9));
                }
            }
            break;

        case 'rpg':
            $cx = $rand(120, 180);
            $art .= sprintf('<circle cx="%.0f" cy="150" r="95" fill="none" stroke="%s" stroke-width="2"/>', $cx, $c($h2, 80, 80, .35));
            $art .= sprintf('<circle cx="%.0f" cy="150" r="70" fill="none" stroke="%s" stroke-width="1" stroke-dasharray="4 6"/>', $cx, $c($h2, 80, 80, .4));
            $art .= sprintf('<polygon points="%1$.0f,62 %2$.0f,150 %1$.0f,250 %3$.0f,150" fill="%4$s"/>', $cx, $cx + 46, $cx - 46, $c($h2, 85, 75, .8));
            $art .= sprintf('<polygon points="%1$.0f,62 %2$.0f,150 %1$.0f,250" fill="%3$s"/>', $cx, $cx + 46, $c($h, 70, 55, .55));
            for ($i = 0; $i < 3; $i++) {
                $x = $rand(30, 270);
                $y = $rand(40, 250);
                $s = $rand(8, 16);
                $art .= sprintf('<polygon points="%1$.0f,%2$.0f %3$.0f,%4$.0f %1$.0f,%5$.0f %6$.0f,%4$.0f" fill="%7$s"/>', $x, $y - $s, $x + $s * .6, $y, $y + $s, $x - $s * .6, $c($h2, 85, 80, .6));
            }
            break;

        case 'puzzle':
            for ($row = 0; $row < 7; $row++) {
                for ($col = 0; $col < 5; $col++) {
                    $on = $rand() > .55;
                    $art .= sprintf('<rect x="%d" y="%d" width="46" height="46" rx="6" fill="%s"/>', 22 + $col * 52, 12 + $row * 52,
                        $on ? $c($rand() > .5 ? $h2 : $h, 75, 70, $rand(.45, .85)) : $c($h, 40, 60, .12));
                }
            }
            break;

        case 'strategy':
            for ($row = 0; $row < 8; $row++) {
                for ($col = 0; $col < 6; $col++) {
                    $x = $col * 54 + ($row % 2) * 27;
                    $y = $row * 40 + 10;
                    $pts = [];
                    for ($k = 0; $k < 6; $k++) {
                        $a = deg2rad(60 * $k + 30);
                        $pts[] = sprintf('%.1f,%.1f', $x + cos($a) * 26, $y + sin($a) * 26);
                    }
                    $fill = $rand() > .72 ? $c($h2, 75, 65, $rand(.4, .8)) : $c($h, 40, 70, .08);
                    $art .= sprintf('<polygon points="%s" fill="%s" stroke="%s" stroke-width="1.5"/>', implode(' ', $pts), $fill, $c($h, 40, 70, .25));
                }
            }
            break;

        case 'simulation':
            $art .= sprintf('<circle cx="%.0f" cy="90" r="34" fill="%s"/>', $rand(60, 240), $c(45, 95, 75, .9));
            $art .= sprintf('<ellipse cx="80" cy="300" rx="200" ry="95" fill="%s"/>', $c($h, 45, 42, .9));
            $art .= sprintf('<ellipse cx="260" cy="320" rx="180" ry="90" fill="%s"/>', $c($h2, 45, 34, 1));
            for ($i = 0; $i < 3; $i++) {
                $x = 60 + $i * 75 + $rand(-10, 10);
                $y = 222 + $rand(0, 30);
                $art .= sprintf('<rect x="%.0f" y="%.0f" width="30" height="24" fill="%s"/>', $x, $y, $c(30, 60, 85, .9));
                $art .= sprintf('<polygon points="%.0f,%.0f %.0f,%.0f %.0f,%.0f" fill="%s"/>', $x - 4, $y, $x + 15, $y - 18, $x + 34, $y, $c(8, 70, 60, .9));
            }
            break;

        case 'shooter':
            $cx = $rand(110, 190);
            $cy = $rand(130, 180);
            foreach ([110, 75, 40] as $i => $r) {
                $art .= sprintf('<circle cx="%.0f" cy="%.0f" r="%d" fill="none" stroke="%s" stroke-width="%d"/>', $cx, $cy, $r, $c($h2, 85, 72, .35 + $i * .2), 3 - $i % 2);
            }
            $art .= sprintf('<path d="M%1$.0f %3$.0f V%4$.0f M%1$.0f %5$.0f V%6$.0f M%7$.0f %2$.0f H%8$.0f M%9$.0f %2$.0f H%10$.0f" stroke="%11$s" stroke-width="3"/>',
                $cx, $cy, $cy - 140, $cy - 18, $cy + 18, $cy + 140, $cx - 140, $cx - 18, $cx + 18, $cx + 140, $c($h2, 90, 80, .8));
            $art .= sprintf('<circle cx="%.0f" cy="%.0f" r="5" fill="%s"/>', $cx, $cy, $c(45, 100, 66));
            break;

        case 'roguelike':
            $cx = $rand(105, 195);
            $rings = (int) round($rand(4, 6));
            $step = $rand(30, 40);
            for ($i = 0; $i < $rings; $i++) {
                $w = 250 - $i * $step;
                $x = $cx - $w / 2;
                $top = 40 + $i * 26;
                $bottom = 300 - $i * 8;
                $art .= sprintf('<path d="M%1$.0f %2$d V%3$.0f A%4$.0f %4$.0f 0 0 1 %5$.0f %3$.0f V%2$d Z" fill="%6$s"/>',
                    $x, $bottom, $top + $w / 2, $w / 2, $x + $w, $c($i % 2 ? $h : $h2, 50, 18 + $i * 9, .9));
            }
            break;

        case 'horror':
            $art .= sprintf('<circle cx="%.0f" cy="95" r="52" fill="%s"/>', $rand(90, 210), $c(50, 30, 88, .9));
            for ($i = 0; $i < 7; $i++) {
                $x = $i * 48 + $rand(-10, 10);
                $top = $rand(120, 210);
                $art .= sprintf('<polygon points="%.0f,320 %.0f,%.0f %.0f,%.0f %.0f,%.0f %.0f,320" fill="%s"/>',
                    $x - 18, $x - 4, $top + 40, $x - 12, $top + 30, $x + 2, $top, $x + 18, $c($h, 30, 7, 1));
            }
            $art .= sprintf('<rect x="0" y="250" width="300" height="80" fill="%s"/>', $c($h, 20, 70, .12));
            break;

        case 'racing':
            $vx = $rand(110, 190);
            $art .= sprintf('<polygon points="%.0f,150 %.0f,150 330,400 -30,400" fill="%s"/>', $vx - 6, $vx + 6, $c($h, 20, 18, .9));
            for ($i = 0; $i < 6; $i++) {
                $y1 = 160 + $i * $i * 7;
                $art .= sprintf('<line x1="%.1f" y1="%.0f" x2="%.1f" y2="%.0f" stroke="%s" stroke-width="%.1f"/>',
                    $vx, $y1, $vx, $y1 + 6 + $i * 4, $c(45, 100, 70, .9), 1 + $i);
            }
            for ($i = 0; $i < 7; $i++) {
                $y = $rand(40, 140);
                $art .= sprintf('<line x1="%.0f" y1="%.0f" x2="%.0f" y2="%.0f" stroke="%s" stroke-width="3" stroke-linecap="round"/>',
                    $rand(0, 120), $y, $rand(160, 300), $y, $c($h2, 90, 75, .4));
            }
            break;

        case 'sports':
            $art .= sprintf('<rect x="20" y="30" width="260" height="270" rx="8" fill="none" stroke="%s" stroke-width="3"/>', $c($h2, 60, 80, .5));
            $art .= sprintf('<line x1="20" y1="165" x2="280" y2="165" stroke="%s" stroke-width="3"/>', $c($h2, 60, 80, .5));
            $art .= sprintf('<circle cx="150" cy="165" r="44" fill="none" stroke="%s" stroke-width="3"/>', $c($h2, 60, 80, .5));
            $art .= sprintf('<circle cx="%.0f" cy="%.0f" r="18" fill="%s"/>', $rand(80, 220), $rand(80, 250), $c(45, 100, 70));
            break;

        case 'fighting':
            foreach ([[$rand(70, 110), $h2], [$rand(190, 230), $h]] as [$x, $hue]) {
                $pts = [];
                for ($k = 0; $k < 16; $k++) {
                    $a = deg2rad($k * 22.5);
                    $r = $k % 2 ? 30 : 74;
                    $pts[] = sprintf('%.1f,%.1f', $x + cos($a) * $r, 160 + sin($a) * $r);
                }
                $art .= sprintf('<polygon points="%s" fill="%s"/>', implode(' ', $pts), $c($hue, 85, 65, .75));
            }
            break;

        case 'party':
            for ($i = 0; $i < 26; $i++) {
                $hue = ($h + (int) $rand(0, 360)) % 360;
                if ($rand() > .5) {
                    $art .= sprintf('<circle cx="%.0f" cy="%.0f" r="%.0f" fill="%s"/>', $rand(0, 300), $rand(0, 320), $rand(5, 16), $c($hue, 85, 68, .8));
                } else {
                    $art .= sprintf('<rect x="%.0f" y="%.0f" width="%.0f" height="8" rx="3" transform="rotate(%.0f %.0f %.0f)" fill="%s"/>',
                        $rand(0, 300), $rand(0, 320), $rand(14, 26), $rand(0, 180), $rand(0, 300), $rand(0, 320), $c($hue, 85, 68, .8));
                }
            }
            break;

        case 'survival':
            for ($i = 0; $i < 5; $i++) {
                $y = 110 + $i * 45;
                $d = "M0 $y";
                for ($x = 0; $x < 300; $x += 60) {
                    $d .= sprintf(' q30 %.0f 60 0', $rand(-24, 24));
                }
                $d .= ' V400 H0 Z';
                $art .= sprintf('<path d="%s" fill="%s"/>', $d, $c($i % 2 ? $h : $h2, 55, 45 - $i * 7, .55));
            }
            for ($i = 0; $i < 8; $i++) {
                $art .= sprintf('<circle cx="%.0f" cy="%.0f" r="%.0f" fill="none" stroke="%s" stroke-width="1.5"/>', $rand(20, 280), $rand(60, 300), $rand(3, 9), $c($h2, 80, 85, .6));
            }
            break;

        default: // action
            for ($i = 0; $i < 5; $i++) {
                $x = $rand(-80, 220);
                $w = $rand(18, 46);
                $art .= sprintf('<polygon points="%.0f,0 %.0f,0 %.0f,330 %.0f,330" fill="%s"/>', $x + 110, $x + 110 + $w, $x + $w, $x, $c($i % 2 ? $h2 : $h, 85, 62, $rand(.35, .75)));
            }
            $art .= sprintf('<circle cx="%.0f" cy="%.0f" r="30" fill="none" stroke="%s" stroke-width="4"/>', $rand(60, 240), $rand(60, 200), $c(45, 100, 70, .9));
            break;
    }

    $top = $c($h, 55, 32);
    $bottom = $c($h2, 50, 11);
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 400" width="300" height="400">
  <defs>
    <linearGradient id="{$uid}bg" x1="0" y1="0" x2="0.4" y2="1">
      <stop offset="0" stop-color="$top"/><stop offset="1" stop-color="$bottom"/>
    </linearGradient>
    <linearGradient id="{$uid}fade" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0.45" stop-color="#0e1122" stop-opacity="0"/><stop offset="1" stop-color="#0e1122" stop-opacity="0.92"/>
    </linearGradient>
    <pattern id="{$uid}grain" width="6" height="6" patternUnits="userSpaceOnUse" patternTransform="rotate(35)">
      <line x1="0" y1="0" x2="0" y2="6" stroke="#ffffff" stroke-opacity="0.035" stroke-width="2"/>
    </pattern>
  </defs>
  <rect width="300" height="400" fill="url(#{$uid}bg)"/>
  $art
  <rect width="300" height="400" fill="url(#{$uid}grain)"/>
  <rect width="300" height="400" fill="url(#{$uid}fade)"/>
</svg>
SVG;
}
