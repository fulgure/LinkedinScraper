/**
 * ABANDONNÉ CAR PhantomJS N'EST PLUS SUPPORTÉ
 *              NE FONCTIONNE PAS
 */
console.log("Loaded");
//Selector for the info section of linkedin's about page : .org-grid__core-rail--no-margin-left > section[id^=ember]
var page = require('webpage').create();

page.onConsoleMessage = function (msg, lineNum, sourceId) {
    console.log('CONSOLEAYY: ' + msg + ' (from line #' + lineNum + ' in "' + sourceId + '")');
};

phantom.addCookie({
    name: "li_at",
    value: "AQEDAS918EkFAhD1AAABcpPFYPcAAAFyt9Hk900AfllJVWi26LpCFvVUrsZNPxqZlANG-0Y096Xzwb1nnnb9FyWrsIw6e00rSS41eMIbviVAKyxbeGZKpdSKv0BRZyGm8vkMi78ZzkHFMjLpgzZXw_Kt",
    domain: '.www.linkedin.com'
})
var URLs = GetAllCompaniesToSearch();
console.log("Got companies");
console.log(URLs);
var companies = [];
for (var i = 0; i < URLs.length; i++) {
    console.log("Company n°" + i);
    console.log(URLs[i]);
    //GetDataFromURL returns null
    companies.push(GetDataFromURL(URLs[i]));
    console.log(companies[i]);
}
console.log(companies[0]);
/**
 * Retourne un object Company rempli avec les infos récupérées dans le linkedin
 * @param {string} url 
 * @returns {Object} c
 */
function GetDataFromURL(url) {
    if (!url.indexOf("/about") !== -1)
        if (url.slice(-1) !== '/')
            url += '/';
    url += "about";
    page.open(url, function (status) {
        page.includeJs("http://code.jquery.com/jquery-3.5.1.min.js", function () {
            console.log(url + " loaded");
            /*
            *   Data layout
            *   Section
            *       h4 -> "Présentation"
            *       p -> description
            * 
            *       dl -> other infos
            *           dt -> header
            *           dd -> data
            */
            var c = {};
            page.evaluate(function () {
                var infos = $(".org-grid__core-rail--no-margin-left > section[id^=ember]");
                c.description = infos.children('p').text();

                var otherInfo = infos.children('dl');
                var nbChilds = otherInfo.children('dt').length;
                c.url = url;
                for (var i = 0; i < nbChilds; i++) {
                    var currData = otherInfo.children('dd').eq(i).text().replace(/^[ ]{1,}(\W)/g, '\1').replace(/[^0-9A-z \/:.éè']/g,'');
                    switch (otherInfo.children('dt').eq(i).text().replace(/\W/g,'')) {
                        case "Site web".replace(/\W/g,''):
                            c.website = currData;
                            break;
                        case "Téléphone".replace(/\W/g,''):
                            c.tel = currData;
                            break;
                        case "Secteur".replace(/\W/g,''):
                            c.industry = currData;
                            break;
                        case "Taille de l'entreprise".replace(/\W/g,''):
                            c.size = currData;
                            break;
                        case "Type".replace(/\W/g,''):
                            c.type = currData;
                            break;
                        case "Fondée en".replace(/\W/g,''):
                            c.foundedDate = currData;
                            break;
                        case "Spécialisations".replace(/\W/g,''):
                            c.specialty;
                            break;
                    }
                }
            });
            page.close();
            return c;
        });
    });
}

function GetAllCompaniesToSearch() {
    var result = [];
    var request = new XMLHttpRequest();
    request.onreadystatechange = function () {
        if (request.responseText.length > 1) {
            var data = JSON.parse(request.responseText);
            for (var i = 0; i < data.length; i++) {
                result.push(data[i]);
            }
        }
    };
    request.open('GET', 'http://127.0.0.1/LinkedInScraper/ajax/GetAllCompaniesToSearch.php', false);
    request.send();
    return result;
}