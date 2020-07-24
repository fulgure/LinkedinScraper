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

<head>
	<style>
		#search * {
			width: 10%;
		}

		.enabled {
			color: Teal !important;
			font-weight: bold;
		}
	</style>
</head>

<body>
	<?php
	$cnt = 0;
	foreach ($matchingCompanies as $match) {
	?>
		<div id="<?= $cnt ?>" class="comp" style="display:none;text-align:center;">
			<h3 class="<?= $cnt ?> name" id="<?= urldecode($match[0]["badName"][2]) ?>" style="color:red;display:hidden">
				<span id="num" class="<?= $cnt ?>"><?= $cnt + 1 ?>.</span>
				<span id="title" class="<?= $cnt ?>"><?= urldecode($match[0]["badName"][1]) ?></span>
			</h3>
			<?php
			if ($match !== null) {
				foreach ($match as $possibility) { ?>
					<button class="<?= $cnt ?> possibility" style="color:green;font-size:12pt;" id="<?= $possibility["fixedName"][2] ?>" value="<?= $possibility["fixedName"][1] ?>"><?= $possibility["fixedName"][1] ?> (<?= intval($possibility["perc"]) ?>%)</button>
			<?php }
			} ?>

		</div>
	<?php
		$cnt++;
	} ?>
	<br>
	<div style="text-align:center;">
		<button class="unassign" style="color:red;font-size:16pt;margin-right:3%" onclick="SetUnassigned()">UNASSIGN</button>
		<button class="unemploy" style="color:red;font-size:16pt;" onclick="SetUnemployed()">UNEMPLOYED</button>
		<br>
		<br>
	</div>
	<hr>
	<div style="text-align:center;">
		<h3 style="color:blue">Recherche</h3>
		<input type="text" id="searchBar" name="search" style="font-size:16pt" />
		<div id="searchTerms" style="display:block;margin-top:1%;margin-bottom:1%;">
		</div>
		<div id="searchResults">
			<h3 class="notFound" style="color:red;display:none;">NO RESULTS</h3>
		</div>
	</div>

	<br>
	<hr>

	<div style="text-align:center" id="search">
		<button class="previous" size="10" style="color:blue;font-size:16pt;margin-top:3%; margin-right:3%" onclick="ChangeCompanyToMatch(-1)">PREVIOUS</button>
		<input type="text" id="currPage" style="font-size:16pt;font-weight:700;margin:3% 3% 0% 0%;text-align:center" maxlength="6" size="6">
		<button class="skip" size="10" style="color:blue;font-size:16pt;margin-top:3%" onclick="ChangeCompanyToMatch(1)">NEXT</button>
		<br>
		<button class="changeIndex" style="color:blue;font-size:16pt;margin-top:1%;" onclick="JumpToCompany()">JUMP TO</button>
	</div>
	<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
	<script>
		const AUTO_SOLR_SEARCH = true;
		const NB_COMPANIES = <?= count($matchingCompanies) ?>;
		const DEFAULT_MINIMUM_WORD_LENGTH = 3;
		const TITLE_SELECTOR = "%s";

		var currentNumber;
		var searchBar;
		var searchResults;
		var currPage;
		var searchTerms;

		var currTerms = '';
		var eventCalledByUser = false;

		$(document).ready(function() {

			currentNumber = 0;
			searchBar = $("#searchBar");
			searchResults = $('#searchResults');
			currPage = $('#currPage');
			searchTerms = $('#searchTerms');

			addClickHandlerToButtons();
			searchBar.off('keyup').on("keyup", function() {
				eventCalledByUser = true;
				SolrSearch($(this).val(), ParseSearchResult);
			})
			currTerms = splitCompanyName(getCurrentTitle());
			addSearchTerms(currTerms);
			if (AUTO_SOLR_SEARCH)
				SolrSearchCurrentOption();
			RefreshUI();
		});

		function getCurrentTitle() {
			return $('span#title.' + currentNumber).text();
		}

		function SolrSearch(text, callback) {
			if (Array.isArray(text))
				text = text.join(' ');

			if (text.length >= 3) {
				$.get("ajax/SolrWrapper.php", {
					query: text
				}, callback);
			}
		}

		function splitCompanyName(name, minWordLength = DEFAULT_MINIMUM_WORD_LENGTH) {
			name = name.replace(/-/g, ' ');
			nameParts = name.split(' ');
			result = [];
			nameParts.forEach(function(el) {
				if (el.length >= DEFAULT_MINIMUM_WORD_LENGTH)
					result.push(el);
			});

			return result;
		}

		function addClickHandlerToButtons() {
			$(".possibility").off('click').on("click", function() {
				var badID = $("h3." + currentNumber).attr("id");
				var goodID = $(this).attr("id");
				SubmitChange(badID, goodID);
			});
		}

		function ParseSearchResult(data) {
			if (data.code > 0) {
				$('.searchResult').remove();
				var results = JSON.parse(data.data);
				if (currTerms.length == 0)
					currTerms = data.terms.reduce((result, val) => result += ' ' + val);

				if (results.response.docs.length > 0) {
					$('.notFound').hide();
					results.response.docs.forEach(function(val, i) {
						addPossibilityButton(val.id, val.baseurl);
					})
					addClickHandlerToButtons();
				} else {
					$('.notFound').show();
				}
			}
			newTerms = splitCompanyName(searchBar.val());
			isEqual = true;
			if(currTerms.length !== newTerms.length)
				isEqual = false;
			else{
				currTerms.forEach((val, i) => isEqual &= (val == newTerms[i]))
			}
			if(!isEqual){
				currTerms = newTerms;
				removeAllSearchTerms();
				addSearchTerms(currTerms);
			}
			RefreshUI();
			eventCalledByUser = false;
		}

		function removeAllSearchTerms() {
			$('.searchTerm').remove();
		}

		function toggleButtonAndSearch(btn) {
			$(btn).toggleClass('enabled');
			SolrSearch(getSearchTerms(), ParseSearchResult);
		}

		function addSearchTerms(terms) {
			terms.forEach(el => addSearchTermButton(el));
		}

		function getSearchTerms() {
			result = '';
			$('.searchTerm.enabled').each(function(i) {
				result += $(this).text() + ' ';
			});
			return result;
		}

		function addSearchTermButton(val) {
			searchTerms.append(`
			<button class="searchTerm enabled" style="color:red;font-size:16pt;" onclick="toggleButtonAndSearch(this)">${val}</button>
			`);
		}

		function addPossibilityButton(id, url) {
			searchResults.append(`
			<button style="color:green;font-size:12pt;margin:0.3% 0% 0.3% 0%" id="${id}" class="searchResult possibility" value="${url}">${url} (${id})</button>
			`);
		}

		function SubmitChange(badID, goodID, shouldUnassign = false) {
			data = {
				"brokenID": badID,
				"correctID": goodID
			};
			if (shouldUnassign)
				data.unassign = true;
			$.get("ajax/FixLinkedin.php", data, function(data) {
				if (data.Code > 0) {
					ChangeCompanyToMatch(1, false);
				} else {
					alert("Error making the request");
				}
			});
		}

		function JumpToCompany() {
			newVal = parseInt(currPage.val()) - 1;
			if (newVal >= 0) {
				relativePos = newVal - currentNumber;
				ChangeCompanyToMatch(relativePos, false);
			}
		}

		const FORWARD = 0;
		const BACKWARD = 1;
		/**
		Change l'entreprise montrée à l'utilisateur à partir d'une position relative
		RelativePosition = 1 : Montre la prochaine entreprise dans la liste
		RelativePosition = -1 : Montre l'entreprise précédente dans la liste
		**/
		function ChangeCompanyToMatch(newRelativePosition, checkForDuplicates = true) {
			if (newRelativePosition == 0)
				return;
			else if (newRelativePosition > 0) {
				direction = FORWARD;
				incrementVal = 1;
			} else {
				direction = BACKWARD;
				incrementVal = -1;
			}
			oldComp = getCurrentTitle();
			/* currentNumber commence à 0, alors que NB_COMPANIES commence à 1 */
			if (direction = FORWARD && currentNumber + newRelativePosition >= NB_COMPANIES)
				currentNumber = NB_COMPANIES - 1;
			/* Relative position est une position relative, donc possiblement négatif */
			/* Si relativePosition = -1, on montre l'entreprise précédente */
			else if (direction == BACKWARD && currentNumber - abs(newRelativePosition) < 0)
				currentNumber = 0;
			else
				currentNumber += newRelativePosition;

			if (checkForDuplicates) {
				shouldSkip = true;
				firstLoop = true;
				while (shouldSkip) {
					currentNumber += incrementVal;
					newComp = getCurrentTitle();
					if (newComp !== oldComp || currentNumber == 0 || currentNumber >= NB_COMPANIES) {
						shouldSkip = false;
						if (firstLoop)
							currentNumber -= incrementVal;
					}
					oldComp = newComp;
					firstLoop = false;
				}
			}

			compName = getCurrentTitle();
			currTerms = splitCompanyName(compName)
			removeAllSearchTerms();
			SolrSearchCurrentOption();
			addSearchTerms(currTerms)
			RefreshUI();
		}

		function RefreshUI() {
			currPage.prop('value', currentNumber + 1);
			if (currTerms.length > 0 && !eventCalledByUser)
				searchBar.prop('value', currTerms.join(' '));
			$(".current").hide().removeClass("current");
			$("#" + currentNumber + ".comp").show().addClass("current");
			console.log(currentNumber);
		}


		function SolrSearchCurrentOption() {
			SolrSearch(currTerms, ParseSearchResult);
		}

		const UNEMPLOYED_ID = <?= CONSTANTS::UNEMPLOYED_COMPANY_ID ?>;
		const UNASSIGNED_ID = <?= CONSTANTS::UNASSIGNED_COMPANY_ID ?>;

		function SetUnemployed() {
			var badID = $("h3." + currentNumber).attr("id");
			SubmitChange(badID, UNEMPLOYED_ID);
		}

		function SetUnassigned() {
			var badID = $("h3." + currentNumber).attr("id");
			SubmitChange(badID, UNASSIGNED_ID, true);
		}
	</script>
</body>