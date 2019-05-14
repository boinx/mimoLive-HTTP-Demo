<style>html{background-color:#000;color:#fff;font-family:monospace;}</style>
<h1>mimoLive API Explorer</h1>
<?php
	/*
	 * mimoLive API Explorer
	 * This simple script loads the complete document structure mimoLive has to offer
	 * for more documentation see: https://docs.mimo.live/docs/http-api
	 * and the other demo scripts at: https://github.com/boinx/mimoLive-HTTP-Demo
	 *
	 * Greetings from Munich,
	 * the Boinx Software Team
	 */

	$mimoLiveURL = 'http://localhost:8989/api/v1';

	//Load all the documents from mimoLive
	$documents = loadDocuments();

	echo '<ul>';
	foreach ($documents as $document)
	{
		//Each element returned from the API has a unique id under `id`
		//Most objects also have a `name` `attribute`
		echo '<li>'.$document->attributes->name.': ('.$document->id.')';

		//Next up load all the layers contained inside the document
		echo '<br /><b>LAYERS</b>';
		$layers = loadLayersForDocument($document);

		echo '<ul>';
		foreach ($layers as $layer)
		{
			//Similiar to the documents each layer has a name and an id
			echo '<li>'.$layer->attributes->name.': ('.$layer->id.')';

			//Get all variants of the layer
			$variants = loadVariantsForLayer($document, $layer);

			echo '<ul>';
			foreach ($variants as $variant)
			{
				//And of course each variant also has a name and id
				echo '<li>'.$variant->attributes->name.': ('.$variant->id.')';

				//For convenience we create some URLs here for common tasks you might do with the API
				//Note these URLs all also work on the layer instead of the variant object

				//Turn on a variant:
				$liveURL = $mimoLiveURL.'/documents/'.$document->id.'/layers/'.$layer->id.'/variants/'.$variant->id.'/setLive';

				//Turn off a variant:
				$offURL = $mimoLiveURL.'/documents/'.$document->id.'/layers/'.$layer->id.'/variants/'.$variant->id.'/setOff';

				//Toggle a variant:
				$toggleURL = $mimoLiveURL.'/documents/'.$document->id.'/layers/'.$layer->id.'/variants/'.$variant->id.'/toggleLive';

				echo '<br><a href="'.$liveURL.'" target="_blank">/setLive</a>';
				echo '<br><a href="'.$offURL.'" target="_blank">/setOff</a>';
				echo '<br><a href="'.$toggleURL.'" target="_blank">/toggleLive</a>';

				//Some layer/variants also support so called "signals", like the stopwatch layer to start/stop it
				//You can get a list of all signals by filtering the `input-values` for keys that end in `_TypeSignal`
				$signals = getSignalsForVariant($variant);
				$signalsBaseURL = $mimoLiveURL.'/documents/'.$document->id.'/layers/'.$layer->id.'/variants/'.$variant->id.'/signals/';

				foreach ($signals as $signal)
				{
					echo '<br><a href="mimoProxy.php?mimoURL='.$signalsBaseURL.$signal.'" target="_blank">/'.$signal.'</a>';
				}

				echo '</li>';
			}

			echo '</ul>';
			echo '</li>';
		}
		echo '</ul>';


		//Beside Layers a Document also contains Sources which we can also access via the API
		echo '<b>SOURCES</b><br />';

		$sources = loadSourcesForDocument($document);
		echo '<ul>';
		foreach ($sources as $source)
		{
			//All sources of course come with a name and an id
			echo '<li>'.$source->attributes->name.': ('.$source->id.')';

			//Plus you can request a MJPEG preview stream for each source, you're welcome :-)
			$mjpegURL = $mimoLiveURL.'/documents/'.$document->id.'/sources/'.$source->id.'/preview?fps=1&width=480&height=320';
			echo '<br /><a href="'.$mjpegURL.'" target="_blank">Open Preview</a>';

			//A source can have filters attached to it:
			$filters = loadFiltersForSource($document, $source);

			echo '<ul>';
			foreach ($filters as $filter)
			{
				echo '<li>'.$filter->attributes->name.': ('.$filter->id.')</li>';
			}

			echo '</ul>';
			echo '</li>';
		}

		echo '</ul>';
		echo '</li>';
	}
	echo '</ul>';

	function loadDocuments()
	{
		global $mimoLiveURL;
		$documentsURL = $mimoLiveURL.'/documents';

		return getJSON($documentsURL);
	}

	function loadLayersForDocument($document)
	{
		global $mimoLiveURL;
		$layersURL = $mimoLiveURL.'/documents/'.$document->id.'/layers';

		return getJSON($layersURL);
	}

	function loadVariantsForLayer($document, $layer)
	{
		global $mimoLiveURL;
		$variantsURL = $mimoLiveURL.'/documents/'.$document->id.'/layers/'.$layer->id.'/variants';

		return getJSON($variantsURL);
	}

	function loadSourcesForDocument($document)
	{
		global $mimoLiveURL;
		$sourcesURL = $mimoLiveURL.'/documents/'.$document->id.'/sources';

		return getJSON($sourcesURL);
	}

	function loadFiltersForSource($document, $source)
	{
		global $mimoLiveURL;
		$filtersURL = $mimoLiveURL.'/documents/'.$document->id.'/sources/'.$source->id.'/filters';

		return getJSON($filtersURL);
	}

	function getSignalsForVariant($variant)
	{
		//To get all the signals a layer supports simply filter the `input-values` array for keys ending with '_TypeSignal'
		$inputValues = $variant->attributes->{'input-values'};
		$inputValues = json_decode(json_encode($inputValues), true);

		$typeSignal = "_TypeSignal";
		$signals = array();

		foreach ($inputValues as $key => $value)
		{
			if (substr_compare($key, $typeSignal, strlen($key)-strlen($typeSignal), strlen($typeSignal)) === 0)
			{
				$signals[] = $key;
			}
		}

		return $signals;
	}

	function getJSON($url)
	{
		$json = file_get_contents($url);
		$data = json_decode($json);

		return $data->data;
	}
