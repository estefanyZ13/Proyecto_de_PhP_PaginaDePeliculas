<?php
/**
 * Genera imágenes SVG placeholder (sin extensión GD)
 */
$img_dir = __DIR__ . '/../assets/img';
if (!file_exists($img_dir)) mkdir($img_dir, 0777, true);

$items = [
    'endgame' => '#1a237e',
    'freeguy' => '#1b5e20',
    'quietplace' => '#b71c1c',
    'conjuring' => '#4a148c',
    'insideout2' => '#e65100',
    'coco' => '#006064',
    'lalaland' => '#880e4f',
    'avatar2' => '#01579b',
    'interstellar' => '#33691e',
    'lionking' => '#bf360c',
    'limitless' => '#37474f',
    'mandalorian' => '#0d47a1',
    'loki' => '#1a237e',
    'wandavision' => '#4a148c',
    'shogun' => '#880e4f',
    'xmen97' => '#e65100',
    'bridgerton' => '#004d40',
    'welcomeearth' => '#006064',
    'monsterswork' => '#1b5e20',
    'placeholder' => '#1c1c2e',
];

$emojis = ['🎬','📺','🍿','⭐','🎭','🎪','🎨','🎯','🌟','🎥','🔥','🎞️','🌙','💫','🏆','🦸','❤️','🌍','👾','🎦'];
$i = 0;

foreach ($items as $name => $color) {
    $file = $img_dir . '/' . $name . '.jpg';
    $emoji = $emojis[$i % count($emojis)];
    $label = strtoupper(substr($name, 0, 10));
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="190" height="285" viewBox="0 0 190 285">
  <defs>
    <linearGradient id="g{$i}" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$color}"/>
      <stop offset="100%" stop-color="#000014"/>
    </linearGradient>
  </defs>
  <rect width="190" height="285" fill="url(#g{$i})"/>
  <rect x="10" y="10" width="170" height="265" rx="4" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/>
  <text x="95" y="120" font-family="Arial,sans-serif" font-size="48" text-anchor="middle" fill="rgba(255,255,255,0.7)">{$emoji}</text>
  <text x="95" y="165" font-family="Arial,sans-serif" font-size="11" text-anchor="middle" fill="rgba(255,255,255,0.5)">{$label}</text>
</svg>
SVG;
    file_put_contents($file, $svg);
    echo "Created: $name.jpg\n";
    $i++;
}

// Avatares
$av_colors = ['#1a237e','#006064','#880e4f','#4a148c'];
$av_labels = ['A1','A2','A3','A4'];
for ($j = 1; $j <= 4; $j++) {
    $file = $img_dir . '/avatar' . $j . '.png';
    $color = $av_colors[$j-1];
    $label = $av_labels[$j-1];
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
  <circle cx="50" cy="50" r="50" fill="{$color}"/>
  <circle cx="50" cy="38" r="16" fill="rgba(255,255,255,0.7)"/>
  <ellipse cx="50" cy="80" rx="26" ry="20" fill="rgba(255,255,255,0.5)"/>
  <text x="50" y="53" font-family="Arial,sans-serif" font-size="14" font-weight="bold" text-anchor="middle" fill="rgba(255,255,255,0.4)">{$label}</text>
</svg>
SVG;
    file_put_contents($file, $svg);
    echo "Created: avatar{$j}.png\n";
}

echo "\n✓ Todas las imágenes generadas correctamente.";
?>
