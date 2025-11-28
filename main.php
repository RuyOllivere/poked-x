<?php

class PokemonFetcher {
    private $apiUrl = "https://pokeapi.co/api/v2/pokemon/";

    public function fetchPokemon($name) {
        $url = $this->apiUrl . strtolower($name);
        $response = file_get_contents($url);
        if ($response === FALSE) {
            return null;
        }
        return json_decode($response, true);
    }

    public function getPokemonData($name) {
        $data = $this->fetchPokemon($name);
        if ($data === null) {
            return "Pokemon not found.";
        }

        $pokemonInfo = [
            'name' => ucfirst($data['name']),
            'id' => $data['id'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'types' => array_map(function($type) {
                return $type['type']['name'];
            }, $data['types']),
            'sprite' => $data['sprites']['front_default'],
            'evolution_chain' => $data['species']['url'],
            'Location areas' => $data['location_area_encounters']
        ];

        return $pokemonInfo;
    }
}
$name = isset($_GET['pokemonName']) ? $_GET['pokemonName'] : null;
$pokemonData = null;

if ($name !== null) {
    $fetcher = new PokemonFetcher();
    $pokemonData = $fetcher->getPokemonData($name);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
      <div class="container">
    <div class="left-screen">
      <div class="left-screen__top">
        <div class="light-container">
          <div class="light light--blue">
          </div>
        </div>
        <div class="light light--red"></div>
        <div class="light light--yellow"></div>
        <div class="light light--green"></div>
      </div>
      <div class="left-screen__bottom">
        <div class="main-screen">
          <div class="main-screen__top-lights">
          </div>
          <div id="display" class="main-screen__display">
            <?php if (is_array($pokemonData) && !empty($pokemonData['sprite'])): ?>
                <div class="pokemon-image">
                    <img src="<?php echo htmlspecialchars($pokemonData['sprite'], ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($pokemonData['name'] ?? 'Pokemon', ENT_QUOTES); ?>">
                </div>
                <div class="search-message" style="display:none;">Searching...</div>
            <?php elseif ($pokemonData === "Pokemon not found."): ?>
                <div class="not-found-message">Pokemon <br>Not Found</div>
            <?php else: ?>
                <div class="search-message">Search a Pokemon</div>
            <?php endif; ?>
          </div>
          <div class="main-screen__speaker-light"></div>
          <div class="main-screen__speaker">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
          </div>
        </div>
      </div>
      <div class="left-screen__joint">
        <div class="joint"></div>
        <div class="joint"></div>
        <div class="joint"></div>
        <div class="joint"></div>
        <div class="joint__reflextion"></div>
      </div>
    </div>
    <div class="right-screen">
      <div class="right-screen__top">
        <div></div>
      </div>    
      <div class="right-screen__bottom">
        <div class="info-container">
            <form method="GET">
                <input id="search" type="text" class="info-input" placeholder="Search Pokemon Name or ID" name="pokemonName">
                <button id="search-btn" type="submit" class="info-btn">Search</button>
            </form>
          
          <section class="info-screen">
            <div id="species" class="info">
              <div class="label">Species:</div>
              <div class="desc"><?php echo is_array($pokemonData) ? htmlspecialchars($pokemonData['name'] ?? '-', ENT_QUOTES) : '...'; ?></div>
            </div>
            <div id="type" class="info">
              <div class="label">Type:</div>
              <div class="desc"><?php echo is_array($pokemonData) && !empty($pokemonData['types']) ? htmlspecialchars(implode(', ', $pokemonData['types']), ENT_QUOTES) : '...'; ?></div>
            </div>
            <div id="height" class="info">
              <div class="label">Height:</div>
              <div class="desc"><?php echo is_array($pokemonData) ? htmlspecialchars((string)$pokemonData['height'], ENT_QUOTES) : '...'; ?></div>
            </div>
            <div id="weight" class="info">
              <div class="label">Weight:</div>
              <div class="desc"><?php echo is_array($pokemonData) ? htmlspecialchars((string)$pokemonData['weight'], ENT_QUOTES) : '...'; ?></div>
            </div>
            <div id="evolution" class="info">
              <div class="label">Location Areas:</div>
              <div class="desc"><?php echo is_array($pokemonData) ? htmlspecialchars($pokemonData['location_area_encounters'] ?? '', ENT_QUOTES) : '...'; ?></div>
            </div>
            <div id="bio" class="info">
              <div class="label">Description:</div>
              <div class="desc"><?php echo is_array($pokemonData) ? '' : '...'; ?></div>
            </div>
          </section>
        </div>
      </div>
    </div>
  </div>
</body>
</html>