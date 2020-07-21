from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from urllib.parse import urlparse
import json
import re

# Récupère le lien du profil LinkedIn de toutes les entreprises à scrapper
def GetAllCompaniesToSearch(driver):
    driver.get(
        "view-source:http://127.0.0.1/LinkedInScraper/ajax/GetAllCompaniesToSearch.php")
    text = driver.find_element(By.CLASS_NAME, "line-content").text
    allURLs = json.loads(text)
    return allURLs

# Formatte l'url de façon à ce qu'elle pointe à la page "À propos"
def FormatURL(url):
    if "/about" not in url:
        url += "/about"
    return url

# Set le cookie d'authentification LinkedIn
def SetupLinkedinCookies(driver):
    # Il faut être se trouver sur le domaine pour pouvoir set le cookie de connexion
    driver.get("http://www.linkedin.com")
    driver.add_cookie({
        "name": "li_at",
        "value": "AQEDAS918EkBZSs_AAABcsLDzJQAAAFy5tBQlE0AqzzDyfht__dd-GngGBm95tZjALPVd74mY4SdLdKiTdPv8yhwq-zFKeEz8a7jo2I9QPc_fr3Mt6mj_AQlvxfQ8hxdv_bRgIu8gkLQfQpzU4jG09LA"
    })

# Prend une liste de WebElement, et retourne une liste contenant le texte de chaque élément
def GetAllTextFromWebElementList(elements):
    result = list()
    for el in elements:
        result.append(el.text)
    return result


# Le sélécteur CSS de la section contenant les infos de l'entreprise
SECTION_CSS_SELECTOR = ".org-grid__core-rail--no-margin-left > section[id^=ember]"
# Le sélécteur CSS lorsque l'entreprise ne possède pas de description
INFO_CSS_SELECTOR = ".org-grid__core-rail--wide section"
# Prend un lien linkedin, et retourne les informations de l'entreprise associée
def GetDataFromURL(driver, url):
    result = Company(url)
    url = FormatURL(url)
    driver.get(url)
    ##
    #   Data layout
    #   Section
    #       h4 -> "Présentation"
    #       p -> description
    #
    #       dl -> other infos
    #           dt -> header
    #           dd -> data
    ##
    try:
        infosPanel = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, SECTION_CSS_SELECTOR))
        )
        result.Description = infosPanel.find_element(By.TAG_NAME, "p").text
    except:
                infosPanel = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, INFO_CSS_SELECTOR))
        )
    

    remainingInfo = infosPanel.find_element(By.TAG_NAME, "dl")
    remainingCategories = remainingInfo.find_elements(By.TAG_NAME, "dt")
    remainingData = remainingInfo.find_elements(By.TAG_NAME, "dd")

    filteredRemainingData = list()
    for d in remainingData:
        # Nombre d'employés : 50-200
        # Dont 75 sur LinkedIn
        # ^^^^^^^^^^^^^^^^^^^^
        # Saute la ligne ci-dessus car inutile
        if("sur LinkedIn" in d.text):
            continue
        try:
            potentialChild = d.find_element(By.TAG_NAME, 'span')
            filteredRemainingData.append(potentialChild)
        except:
            filteredRemainingData.append(d)

    remainingCategories = GetAllTextFromWebElementList(remainingCategories)
    filteredRemainingData = GetAllTextFromWebElementList(filteredRemainingData)
    dictionary = dict(list(zip(remainingCategories, filteredRemainingData)))
    for cat in remainingCategories:
        data:dict = dictionary[cat]
        if(cat.startswith("Site")):
            result.Website = data
        elif(cat == "Téléphone"):
            result.Tel = data
        elif(cat == "Secteur"):
            result.Industry = data
        elif(cat.startswith("Taille")):
            # /!\ Ce n'est pas un espace entre 10 et 000 mais un demi-espace (" " vs " ") /!\
            if(" " in data):
                if("10 001" in data):
                    data = "10001"
                # IDEM
                elif("10 000" in data):
                    data = "10000"
                elif("5 000" in data):
                    data = "5000"
                elif("1 000" in data):
                    data = "1000"
            else:
                # XX-XXX Employés
                #  ^   ^
                upperBoundStartIndex = data.index('-') + 1
                upperBoundStopIndex = data.index(' ')
                data = data[upperBoundStartIndex:upperBoundStopIndex]
            result.Size = data
        elif(cat == "Type"):
            result.CompType = data
        elif(cat == "Fondée en"):
            result.CreatedDate = data
        elif(cat == "Spécialisations"):
            result.Specialty = data
    return result

# Structure de donnée contenant les champs de la base de donnée
class Company:
    LinkedInURL = None
    Description = None
    Website = None
    Tel = None
    Industry = None
    Size = 0
    CompType = None
    CreatedDate = 0
    Specialty = None

    def __init__(self, LinkedInURL):
        self.LinkedInURL = LinkedInURL

# Insère un {Company} en base de donnée
def SaveDataInDB(driver, data):
    baseURL = "http://127.0.0.1/LinkedInScraper/ajax/SaveCompanyInfo.php"
    fullURL = f"{baseURL}?linkedin={data.LinkedInURL}&website={data.Website}&phone={data.Tel}&nbEmployees={data.Size}&industry={data.Industry}&desc={data.Description}&type={data.CompType}&year={data.CreatedDate}&spec={data.Specialty}"
    driver.get(fullURL)


driver = webdriver.Chrome()
URLs = GetAllCompaniesToSearch(driver)
companies = list()
SetupLinkedinCookies(driver)
for u in URLs:
    data = GetDataFromURL(driver, u)
    if(len(list(vars(data).keys())) >= 2):
        SaveDataInDB(driver, data)
    else:
        print(f"{data.Website};{data.Tel};{data.Industry};{data.Size};{data.CompType};{data.CreatedDate};{data.Specialty}")
