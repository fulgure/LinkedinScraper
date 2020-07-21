from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import json
import re
import urllib.parse

# Récupère le lien du profil LinkedIn de toutes les entreprises à scrapper


def GetAllCompaniesToSearch(driver):
    driver.get(
        "view-source:http://127.0.0.1/LinkedInScraper/ajax/GetCompaniesWithEmptyName.php")
    text = driver.find_element(By.CLASS_NAME, "line-content").text
    allURLs = json.loads(text)
    return allURLs

# Set le cookie d'authentification LinkedIn


def SetupLinkedinCookies(driver):
    # Il faut être se trouver sur le domaine pour pouvoir set le cookie de connexion
    driver.get("http://www.linkedin.com")
    driver.add_cookie({
        "name": "li_at",
        "value": "AQEDAS918EkBZSs_AAABcsLDzJQAAAFy5tBQlE0AqzzDyfht__dd-GngGBm95tZjALPVd74mY4SdLdKiTdPv8yhwq-zFKeEz8a7jo2I9QPc_fr3Mt6mj_AQlvxfQ8hxdv_bRgIu8gkLQfQpzU4jG09LA"
    })


NAME_CSS_SELECTOR = "h1 span[dir=ltr]"
# Prend un lien linkedin, et retourne le nom de l'entreprise associée
def GetNameFromURL(driver, url):
    result = Company(url)
    driver.get(url)
    name = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located(
            (By.CSS_SELECTOR, NAME_CSS_SELECTOR))
    )
    result.Name = name.text
    return result

SAVE_NAME_URL = "http://127.0.0.1/LinkedInScraper/ajax/SaveCompanyName.php"
def SaveDataInDB(driver, data):
    fullUrl = f"{SAVE_NAME_URL}?linkedin={data.LinkedInURL}&name={urllib.parse.quote(data.Name)}"
    driver.get(fullUrl)

# Structure de donnée représentant une entreprise
class Company:
    LinkedInURL = None
    Name = None

    def __init__(self, LinkedInURL):
        self.LinkedInURL = LinkedInURL


driver = webdriver.Chrome()
URLs = GetAllCompaniesToSearch(driver)
companies = list()
SetupLinkedinCookies(driver)
for u in URLs:
    data = GetNameFromURL(driver, u)
    if(SaveDataInDB(driver,data)):
        print("Success")
