<?php
/**
 * @TODO Implement lazy-loading for companies. As of right now, the page will timeout if too many companies are loaded at once
 */
require_once "includeAll.php";
$companies = GetAllNonMatchedCompanies();
$filteredComps = [];
foreach ($companies as $comp) {
	array_push($filteredComps, new EPotentialURL($comp["id"], $comp["url"], CONSTANTS::SHOULD_SPLIT));
}
$allNames = GetAllNamesFromCorrectCompanies(CONSTANTS::SHOULD_SPLIT);
$matchingCompanies = [];
foreach ($filteredComps as $comp) {
	array_push($matchingCompanies, GetClosestMatchingCompaniesFromList($comp, $allNames));
}

?>

<body>
	<?php
	$cnt = 0;
	foreach ($matchingCompanies as $match) {
	?>
		<div id="<?= $cnt ?>" style="display:none;text-align:center;">
			<h3 class="<?= $cnt ?>" id="<?= $match[0]["badName"][2] ?>" style="color:red;display:hidden"><?= $match[0]["badName"][1] ?></h3>
			<?php
			if ($match !== null) {
				foreach ($match as $possibility) { ?>
					<button class="<?= $cnt ?> possibility" style="color:green;font-size:12pt;" id="<?= $possibility["fixedName"][2] ?>" value="<?= $possibility["fixedName"][1] ?>"><?= $possibility["fixedName"][1] ?> (<?= intval($possibility["perc"]) ?>%)</button>
			<?php }
			} ?>
			<br>
			<br>
			<button class="<?= $cnt ?> skip" style="color:red;font-size:16pt;margin-right:3%" onclick="ShowNext()">SKIP</button>
			<button class="<?= $cnt ?> unassign" style="color:red;font-size:16pt;margin-right:3%" onclick="SetUnassigned()">UNASSIGN</button>
			<button class="<?= $cnt ?> unemploy" style="color:red;font-size:16pt" onclick="SetUnemployed()">UNEMPLOYED</button>

		</div>
	<?php
		$cnt++;
	}
	?>
	<br>
	<br>
	<div style="text-align:center;">
		<label for="search">Rechercher : </label>
		<input type="text" id="searchBar" name="search" />
		<br>
		<div id="searchResults">

		</div>
	</div>
	<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
	<script>
		var currentNumber = 0;
		const AUTO_SOLR_SEARCH = true;

		$(document).ready(function() {
			$('#' + currentNumber).show();
			addClickHandlerToButtons();
			$("#searchBar").on("keyup", function() {
				SolrSearch($(this).val());
			})
			if(AUTO_SOLR_SEARCH)
				SolrSearchCurrentOption();
		});

		function SolrSearch(text, callback = ParseSearchResult) {
			if (text.length >= 3) {
				$.get("ajax/SolrWrapper.php", {
					query: text
				}, callback);
			}
		}

		function addClickHandlerToButtons() {
			$(".possibility").on("click", function() {
				var badID = $("h3." + currentNumber).attr("id");
				var goodID = $(this).attr("id");
				SubmitChange(badID, goodID);
			});
		}

		function ParseSearchResult(data) {
			if (data.code > 0) {
				$('.searchResult').remove();
				var results = JSON.parse(data.data);
				results.response.docs.forEach(function(val, i) {
					addPossibilityButton(val.id,val.baseurl);
				})
				addClickHandlerToButtons();
			}
		}

		function addPossibilityButton(id, url) {
			$("#searchResults").append(`
			<button style="color:green;font-size:12pt;" id="${id}" class="searchResult possibility" value="${url}">${url} (${id})</button>
			`);
		}

		function SubmitChange(badID, goodID, shouldUnassign = false) {
			data = {
				"brokenID": badID,
				"correctID": goodID
			};
			if(shouldUnassign)
				data.unassign = true;
			$.get("ajax/FixLinkedin.php", data, function(data) {
				if (data.Code > 0) {
					ShowNext();
				} else {
					alert("Error making the request");
				}
			});
		}

		function ShowNext() {
			$("#" + currentNumber).remove();
			currentNumber++;
			if ($('#' + currentNumber).children('button').length <= 2) {
				ShowNext();
				return;
			}
			$("#" + currentNumber).show();
			if(AUTO_SOLR_SEARCH)
				SolrSearchCurrentOption();
			
			console.log(currentNumber);
		}

		function SolrSearchCurrentOption(){
			var currText = $("h3." + currentNumber).text();
			$("#searchBar").val(currText);
			SolrSearch(currText);
		}

		const UNEMPLOYED_ID = <?=CONSTANTS::UNEMPLOYED_COMPANY_ID?>;
		const UNASSIGNED_ID = <?=CONSTANTS::UNASSIGNED_COMPANY_ID?>;

		function SetUnemployed() {
			var badID = $("h3." + currentNumber).attr("id");
			SubmitChange(badID, UNEMPLOYED_ID);
		}

		function SetUnassigned(){
			var badID = $("h3." + currentNumber).attr("id");
			SubmitChange(badID, UNASSIGNED_ID, true);
		}
	</script>
</body>