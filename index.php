<?php 
class Playlist{
	
	public $cidade;
	public $temperatura;
	public $token;
	public $idLista;
	public $playlist;
	
	public function index($city)
	{
		$this->cidade = $city;
		$this->temperatura = $this->buscaTemp();
		if(is_numeric($this->temperatura))
		{
			if($this->temperatura >= 25)
			{
				$this->idLista = "37i9dQZF1DX1ngEVM0lKrb";//Playlist Pop Internacional do Spotify
				$this->playlist = "Pop Internacional";
			}
			elseif($this->temperatura < 25 && $this->temperatura >= 10)
			{
				$this->idLista = "37i9dQZF1DWXRqgorJj26U";//Playlist Rock do Spotify
				$this->playlist = "Rock";
			}
			else //<10
			{
				$this->idLista = "6dI1MmIBasFV59ritLTxIJ";//Playlist Clássicas - Piano e Violino
				$this->playlist = "Clássicas - Piano e Violino";
			}

			$this->token = $this->geraToken();
			if($this->token == "erro")
			{
				$arr = array("errorId" => 2, "ErrorDesc" => "Não foi possível gerar o acess token para realizar a busca");
				return json_encode($arr);
			}
			else
			{
				$playlist = $this->buscaMusicas();
				if($playlist == "erro")
				{
					$txt = $this->temperatura >= 20 ? "Está tão ficando tão quente que a playlist derreteu :(" : "Está ficando tão frio que a playlist congelou :(";

					$arr = array("errorId" => 3, "ErrorDesc" => "A temperatura atual é " . $this->temperatura . "ºC. " . $txt);
					return json_encode($arr);
				}
				else
					return $playlist;
			}
		}
		else
		{
			$arr = array("errorId" => 1, "ErrorDesc" => $this->temperatura);
			return json_encode($arr);
		}
	}
	
	public function buscaTemp()
	{
		$url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode( $this->cidade ) . '&appid=4f7ac7ce7786c54476942f4d0d2a8742&units=metric';
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
		$contents = curl_exec($ch);
		curl_close($ch);
		$json = json_decode($contents, true);

		return isset($json['main']['temp']) ? $json['main']['temp'] : "Não foi possível retornar a informação da cidade";
	}
	
	public function geraToken()
	{
		$header[] = "Authorization: Basic " . base64_encode("e8c78ef8e2334048992cd6d1f5aa046c:cd660e86eb184a14ab56957d20cfa482");
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch,CURLOPT_POSTFIELDS , "grant_type=client_credentials" );
		$contents = curl_exec($ch);
		curl_close($ch);
		$json = json_decode($contents, true);
		
		return isset($json['access_token']) ? $json['access_token'] : "erro";
	}
	
	public function buscaMusicas()
	{
		$header[] = "Authorization: Bearer " . $this->token;

		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, 'https://api.spotify.com/v1/playlists/' . $this->idLista . '/tracks');
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
		$contents = curl_exec($ch);
		curl_close($ch);
		$json = json_decode($contents, true);
		
		if(isset($json['items']))
		{
			$arr = array();
			$arr['temperatura'] = $this->temperatura;
			$arr['playlist'] = $this->playlist;
			foreach($json['items'] as $i)
				$arr[] = $i['track']['name'] . " (" . $i['track']['artists'][0]['name'] . ")";

			return json_encode( $arr );
		}
		else
			return "erro";
	}
	
	
}

$cidade = $_REQUEST['cidade'] ? $_REQUEST['cidade'] : "";
if($cidade)
{
	$busca = $cidade;
	$busca .= $_REQUEST['pais'] ? "," . $_REQUEST['pais'] : "";
	$play = new Playlist();
	$resultado = $play->index($busca);
	echo $resultado;
}
else
{
	$arr = array("errorId" => 4, "ErrorDesc" => "Você precisa passar uma cidade como parâmetro");
	echo json_encode($arr);
}
?>
