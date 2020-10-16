import requests
from bs4 import BeautifulSoup
import psycopg2
connection = psycopg2.connect(user = "pg",
                                  password = "pgpass",
                                  host = "127.0.0.1",
                                  port = "5432",
                                  database = "pgdb")


cursor = connection.cursor()
cursor.execute("SELECT version();")
record = cursor.fetchone()


for game_id in range(687, 6761):
	URL = 'http://www.j-archive.com/showgame.php?game_id=' + str(game_id)
	page = requests.get(URL)
	soup = BeautifulSoup(page.content, 'html.parser')
	title_result = soup.find(id = 'game_title').find('h1');
	year = title_result.get_text()[-4:]
	categories = [category.get_text() for category in soup.find_all(class_ = 'category_name')]
	questions = [clue.get_text() for clue in soup.find_all(class_ = 'clue_text')]
	answers = []
	rows = []
	parses = -1
	question_number = 0
	for clue in soup.findAll(class_ = 'clue'):
		parses = parses + 1
		try:
			tag = clue.find('div',onmouseover=True)
			parseText = str(tag['onmouseover'])
			tag1 = '<em class="correct_response">'
			tag2 = '</em>'
			i1 = parseText.index(tag1)
			i2 = parseText.index(tag2)
			answer = parseText[i1+len(tag1):i2]
			row = []
			row.append(int(year))
			category_num = parses % 6
			if parses >= 30:
				category_num += 6
			row.append(categories[category_num])
			row.append(questions[question_number])
			row.append(answer)
			row.append(game_id)
			rows.append(row)
			question_number = question_number + 1
		except:
			continue;
	cursor.executemany("INSERT INTO jeopardy_questions (year, category, question, answer, game_id) VALUES(%s,%s,%s,%s,%s)", rows)
	connection.commit()

