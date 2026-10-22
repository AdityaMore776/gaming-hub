<?php
/*
 * Gaming Hub — seed data (single source of truth for the demo database).
 *
 * build_sql.php reads this file and writes database/gaming_hub.sql.
 * You only need this if you want to change the seed data; to run the site,
 * just import gaming_hub.sql.
 *
 * Game DNA scores (1-10) are EDITORIAL ESTIMATES written by our team.
 * They are not official developer statistics.
 *
 * Game row format:
 *   [title, year, developer, [genre slugs], [platform slugs], modes,
 *    [story, combat, exploration, difficulty, replayability],
 *    session ('short'|'medium'|'long'), completion hours (null = open-ended),
 *    description, pros (| separated), cons (| separated), featured (bool)]
 *
 * modes: S = single-player, C = co-op, M = competitive / online multiplayer
 */

return [

'genres' => [
    'action' => 'Action', 'adventure' => 'Adventure', 'rpg' => 'RPG',
    'platformer' => 'Platformer', 'puzzle' => 'Puzzle', 'strategy' => 'Strategy',
    'simulation' => 'Simulation', 'shooter' => 'Shooter', 'roguelike' => 'Roguelike',
    'horror' => 'Horror', 'racing' => 'Racing', 'sports' => 'Sports',
    'fighting' => 'Fighting', 'party' => 'Party', 'survival' => 'Survival',
],

'platforms' => [
    'pc' => 'PC', 'playstation' => 'PlayStation', 'xbox' => 'Xbox',
    'switch' => 'Nintendo Switch', 'mobile' => 'Mobile',
],

'games' => [
['Hollow Knight', 2017, 'Team Cherry', ['platformer','action'], ['pc','playstation','xbox','switch'], 'S',
 [5,8,10,8,6], 'medium', 27,
 'A hand-drawn action platformer set in Hallownest, a ruined underground kingdom of insects. You explore a huge interconnected map, unlock new movement abilities and take on demanding boss fights.',
 'Huge, interconnected world full of secrets|Tight, responsive combat|Beautiful hand-drawn art and music',
 'Easy to get lost without buying maps|Some bosses and areas are very punishing', false],

['Stardew Valley', 2016, 'ConcernedApe', ['simulation','rpg'], ['pc','playstation','xbox','switch','mobile'], 'SC',
 [4,2,6,2,9], 'short', 53,
 'You inherit your grandfather\'s run-down farm and slowly turn it into a home: growing crops, raising animals, fishing, mining and getting to know the people of Pelican Town.',
 'Relaxing day-by-day routine|Lots of freedom in how you spend your time|Online and local co-op farms',
 'The in-game clock can feel stressful early on|Progress is slow by design', true],

['Celeste', 2018, 'Maddy Makes Games', ['platformer'], ['pc','playstation','xbox','switch'], 'S',
 [7,1,4,9,6], 'short', 8,
 'A precision platformer about Madeline climbing Celeste Mountain while dealing with anxiety. Short, hard screens with instant respawns, plus an assist mode that lets anyone adjust the challenge.',
 'Precise, satisfying controls|Heartfelt story about mental health|Assist mode makes it approachable',
 'Later chapters and B-sides are extremely demanding|Little combat or exploration', true],

['Hades', 2020, 'Supergiant Games', ['roguelike','action'], ['pc','playstation','xbox','switch'], 'S',
 [7,9,4,7,10], 'short', 22,
 'Play as Zagreus, son of Hades, fighting your way out of the Underworld again and again. Each escape attempt is a short run, and the story keeps moving forward every time you die.',
 'Fast, varied combat with many weapon builds|Story progresses even when you fail|Runs fit into short sessions',
 'Repetition is part of the design|Early runs can feel hard before upgrades', true],

['The Witcher 3: Wild Hunt', 2015, 'CD Projekt Red', ['rpg','adventure'], ['pc','playstation','xbox','switch'], 'S',
 [10,7,9,5,6], 'long', 52,
 'Geralt of Rivia, a monster hunter for hire, searches for his adopted daughter across a war-torn open world. Known for its side quests, which often have the same care as the main story.',
 'Memorable characters and quest writing|Large open world with lots to do|Choices with real consequences',
 'Combat feels stiff compared to newer games|Very long; best enjoyed in long sittings', false],

['Elden Ring', 2022, 'FromSoftware', ['rpg','action'], ['pc','playstation','xbox'], 'SCM',
 [6,10,10,10,7], 'long', 60,
 'An open-world action RPG from the makers of Dark Souls. You roam the Lands Between, find hidden dungeons and face extremely tough bosses, with online co-op summons and invasions.',
 'Exploration constantly rewards curiosity|Deep, flexible character builds|Many ways to approach hard fights',
 'Very high difficulty|Story is told indirectly and is easy to miss', true],

['Portal 2', 2011, 'Valve', ['puzzle'], ['pc','playstation','xbox','switch'], 'SC',
 [8,1,3,5,5], 'short', 9,
 'A first-person puzzle game built around a portal gun that links two surfaces. Includes a funny single-player story and a separate two-player co-op campaign.',
 'Clever puzzles that teach without tutorials|Sharp, funny writing|Excellent co-op campaign',
 'Little reason to replay once solved|No combat at all', false],

['Minecraft', 2011, 'Mojang Studios', ['survival','adventure'], ['pc','playstation','xbox','switch','mobile'], 'SCM',
 [2,4,10,3,10], 'medium', null,
 'An open-ended block-building game. Gather resources, craft tools, build anything you can imagine, and survive the night alone or with friends.',
 'Almost unlimited creative freedom|Great with friends|Runs on nearly every device',
 'Very little guidance for new players|No real story', false],

['It Takes Two', 2021, 'Hazelight Studios', ['adventure','platformer'], ['pc','playstation','xbox','switch'], 'C',
 [7,4,5,3,5], 'medium', 14,
 'A co-op-only adventure about a couple on the verge of divorce who are turned into dolls. Every chapter introduces new mechanics that both players must use together.',
 'Constant variety of gameplay ideas|Designed from the ground up for two players|Friend\'s Pass lets a second player join free',
 'Requires a second player — there is no solo mode|Story tone does not land for everyone', true],

['Red Dead Redemption 2', 2018, 'Rockstar Games', ['action','adventure'], ['pc','playstation','xbox'], 'SM',
 [10,7,10,4,5], 'long', 50,
 'An outlaw story set in 1899 America following Arthur Morgan and the Van der Linde gang. A slow, detailed open world with hunting, camps and a long, character-driven story.',
 'Outstanding story and characters|Incredibly detailed world|Lots of side activities',
 'Slow pace and deliberate controls|Very long campaign', false],

['Disco Elysium', 2019, 'ZA/UM', ['rpg'], ['pc','playstation','xbox','switch'], 'S',
 [10,1,7,4,7], 'medium', 23,
 'A detective RPG with no traditional combat. You solve a murder by talking, thinking and failing skill checks, while the voices of your own skills argue with you.',
 'Exceptional writing and dialogue|Skills shape the story in surprising ways|Many different ways to play your detective',
 'Lots of reading|No combat for players who want action', true],

['Rocket League', 2015, 'Psyonix', ['sports','racing'], ['pc','playstation','xbox','switch'], 'SCM',
 [1,2,1,6,10], 'short', null,
 'Soccer played with rocket-powered cars. Matches are five minutes long, easy to understand and hard to master.',
 'Short matches that fit any schedule|Huge skill ceiling|Local split-screen and online play',
 'Steep learning curve for aerial play|Mostly competitive online', false],

['Among Us', 2018, 'Innersloth', ['party'], ['pc','playstation','xbox','switch','mobile'], 'M',
 [1,1,2,3,8], 'short', null,
 'A social deduction game for 4–15 players. Crewmates complete tasks while hidden impostors try to sabotage the ship, and everyone votes on who to throw out.',
 'Very easy to learn|Great with a group of friends|Plays on phones and PCs together',
 'Needs a group to be fun|Experience depends on who you play with', false],

['Slay the Spire', 2019, 'Mega Crit', ['roguelike','strategy'], ['pc','playstation','xbox','switch','mobile'], 'S',
 [2,6,3,7,10], 'short', 12,
 'A deck-building roguelike where you climb a tower, add cards to your deck and plan each turn against enemies whose next move you can see.',
 'Every run plays differently|Deep strategy with simple rules|Runs pause and resume easily',
 'Minimal story|Losing a long run can sting', false],

['Baldur\'s Gate 3', 2023, 'Larian Studios', ['rpg','strategy'], ['pc','playstation','xbox'], 'SC',
 [10,8,8,6,9], 'long', 75,
 'A party-based RPG built on Dungeons & Dragons 5th Edition rules. Turn-based battles, a reactive story and companions who respond to your choices, playable solo or in co-op.',
 'Choices change the story in big ways|Tactical turn-based combat|Full campaign is playable in co-op',
 'Very long|Turn-based combat can feel slow', true],

['The Legend of Zelda: Breath of the Wild', 2017, 'Nintendo', ['adventure','action'], ['switch'], 'S',
 [5,7,10,5,6], 'long', 50,
 'Link wakes up in a ruined Hyrule and can go almost anywhere from the start. Climbing, cooking and physics-based problem solving make exploration the heart of the game.',
 'Freedom to go anywhere|Systems that encourage experimenting|Memorable sense of discovery',
 'Weapons break often|Story is light and mostly told through memories', false],

['Super Mario Odyssey', 2017, 'Nintendo', ['platformer','adventure'], ['switch'], 'SC',
 [3,3,8,3,6], 'medium', 12,
 'Mario travels between colourful kingdoms with Cappy, a hat that lets him take control of enemies and objects. Each kingdom is packed with collectable Power Moons.',
 'Joyful, inventive level design|Easy to pick up, rewarding to master|Simple two-player mode',
 'Main story is fairly easy|Second player has a limited role', false],

['Animal Crossing: New Horizons', 2020, 'Nintendo', ['simulation'], ['switch'], 'SC',
 [2,1,6,1,8], 'short', null,
 'Move to a deserted island and slowly turn it into a village with animal neighbours. The game runs on the real-world clock, encouraging short daily visits.',
 'Calm and stress-free|Lots of decorating freedom|Friends can visit your island',
 'Real-time clock slows progress|Little challenge', false],

['Sekiro: Shadows Die Twice', 2019, 'FromSoftware', ['action','adventure'], ['pc','playstation','xbox'], 'S',
 [6,10,6,10,5], 'medium', 30,
 'A shinobi action game set in a reimagined Sengoku-era Japan. Combat focuses on deflecting attacks at the right moment and breaking your opponent\'s posture.',
 'Intense, rhythmic sword combat|Memorable boss fights|Grappling hook adds vertical movement',
 'Extremely difficult with no difficulty settings|Little character customisation', false],

['Overwatch 2', 2022, 'Blizzard Entertainment', ['shooter'], ['pc','playstation','xbox','switch'], 'M',
 [2,9,1,6,9], 'short', null,
 'A free-to-play 5v5 team shooter. Pick from a large cast of heroes with distinct abilities and work with your team to capture objectives.',
 'Many heroes with very different playstyles|Short matches|Free to play',
 'Requires online play and teamwork|Experience depends on matchmaking', false],

['Valorant', 2020, 'Riot Games', ['shooter'], ['pc','playstation','xbox'], 'M',
 [1,9,1,8,9], 'short', null,
 'A free-to-play 5v5 tactical shooter where precise gunplay meets character abilities. Attackers plant a device and defenders try to stop them.',
 'Very high skill ceiling|Strategic agent abilities|Free to play',
 'Matches can last 30–45 minutes|Punishing for beginners', false],

['Fortnite', 2017, 'Epic Games', ['shooter'], ['pc','playstation','xbox','switch','mobile'], 'CM',
 [2,8,5,5,9], 'short', null,
 'A free-to-play battle royale where up to 100 players land on an island and fight to be the last one standing. Building, squads and frequent seasonal updates.',
 'Free and available almost everywhere|Play with friends in squads|Constantly updated',
 'Online-only|Building mechanics take time to learn', false],

['Outer Wilds', 2019, 'Mobius Digital', ['adventure','puzzle'], ['pc','playstation','xbox','switch'], 'S',
 [9,1,10,5,3], 'medium', 17,
 'Explore a tiny solar system trapped in a 22-minute time loop. Your only progress is knowledge: what you learn lets you reach new places in the next loop.',
 'Unique, curiosity-driven exploration|A mystery that is genuinely surprising|No combat or grinding',
 'Best played knowing nothing — easy to spoil|Very little replay value once solved', true],

['Subnautica', 2018, 'Unknown Worlds', ['survival','adventure'], ['pc','playstation','xbox','switch'], 'S',
 [6,2,10,5,4], 'medium', 30,
 'Crash-land on an ocean planet and survive by crafting equipment, building bases and diving deeper into the unknown.',
 'Wonderful underwater exploration|Satisfying crafting and base-building|Tense deep-sea atmosphere',
 'Can be scary for players afraid of deep water|Resource gathering can feel repetitive', false],

['Terraria', 2011, 'Re-Logic', ['survival','action'], ['pc','playstation','xbox','switch','mobile'], 'SC',
 [2,7,9,5,10], 'medium', 50,
 'A 2D sandbox of digging, building and fighting. Explore procedurally generated worlds, craft gear and take on a long chain of bosses alone or with friends.',
 'Enormous amount of content|Great multiplayer with friends|Lots of boss fights',
 'Hard to know what to do next without a guide|Little story', false],

['Resident Evil 4', 2023, 'Capcom', ['horror','action'], ['pc','playstation','xbox'], 'S',
 [7,8,5,6,6], 'medium', 16,
 'A remake of the classic survival horror action game. Agent Leon Kennedy travels to a rural European village to rescue the president\'s daughter.',
 'Excellent pacing and set pieces|Satisfying combat and parries|Plenty of unlockables for replays',
 'Tense and gory|Inventory management can interrupt the action', false],

['Inside', 2016, 'Playdead', ['puzzle','platformer'], ['pc','playstation','xbox','switch','mobile'], 'S',
 [8,1,4,3,2], 'short', 4,
 'A dark, wordless puzzle-platformer about a boy running through a mysterious and hostile world. Short, atmospheric and full of striking moments.',
 'Haunting atmosphere|Clever environmental puzzles|Finishes in an evening',
 'Very short|Story is left open to interpretation', false],

['Journey', 2012, 'thatgamecompany', ['adventure'], ['pc','playstation','mobile'], 'SC',
 [7,1,7,1,4], 'short', 2,
 'Travel across a vast desert toward a distant mountain. Along the way you may meet another anonymous online player, who can only communicate through musical chimes.',
 'Beautiful music and visuals|Meaningful wordless co-op|Can be finished in one sitting',
 'Very short|Minimal gameplay challenge', false],

['Sid Meier\'s Civilization VI', 2016, 'Firaxis Games', ['strategy'], ['pc','playstation','xbox','switch','mobile'], 'SM',
 [1,5,6,6,10], 'long', null,
 'A turn-based strategy game where you guide a civilisation from the Stone Age to the space age through science, culture, diplomacy or war.',
 'Endless replayability|Many civilisations and victory types|"One more turn" appeal',
 'Single games take many hours|Late-game turns can be slow', false],

['XCOM 2', 2016, 'Firaxis Games', ['strategy'], ['pc','playstation','xbox','switch'], 'SM',
 [5,8,2,8,8], 'medium', 40,
 'Lead a resistance force against an alien occupation of Earth. Turn-based tactical missions mix with base management and squad upgrades.',
 'Tense tactical decisions|Soldiers you get attached to|Permadeath raises the stakes',
 'Missed high-percentage shots can frustrate|Strict mission timers', false],

['Mario Kart 8 Deluxe', 2017, 'Nintendo', ['racing','party'], ['switch'], 'SCM',
 [1,3,2,3,9], 'short', null,
 'Fast, colourful kart racing with items, many tracks and characters. Easy for anyone to pick up, with local split-screen and online racing.',
 'Great for groups and families|Assist options for beginners|Lots of tracks',
 'Items can feel random|Limited single-player content', false],

['Forza Horizon 5', 2021, 'Playground Games', ['racing'], ['pc','xbox','playstation'], 'SCM',
 [2,1,8,3,8], 'medium', null,
 'An open-world racing festival set in Mexico. Drive hundreds of cars across deserts, jungles and cities, with races and events everywhere.',
 'Beautiful open world|Hundreds of cars|Accessible for all skill levels',
 'Story is very light|Can feel overwhelming with activities', false],

['Street Fighter 6', 2023, 'Capcom', ['fighting'], ['pc','playstation','xbox','switch'], 'SM',
 [3,10,3,7,9], 'short', null,
 'A one-on-one fighting game with a roster of world warriors, a beginner-friendly Modern control scheme and a single-player World Tour mode.',
 'Modern controls help newcomers|Deep competitive play|Strong training tools',
 'Online ranked play can be intimidating|Single-player mode is uneven', false],

['Super Smash Bros. Ultimate', 2018, 'Bandai Namco / Sora Ltd.', ['fighting','party'], ['switch'], 'SCM',
 [2,9,2,5,10], 'short', null,
 'A crossover party fighter with a huge roster of characters. Knock opponents off the stage in matches for up to eight players.',
 'Enormous roster|Chaotic fun with friends|Also has deep competitive play',
 'Unlocking all characters takes time|Online play can be laggy', false],

['Cuphead', 2017, 'Studio MDHR', ['platformer','action'], ['pc','playstation','xbox','switch'], 'SC',
 [2,9,2,9,6], 'short', 11,
 'A run-and-gun game made of back-to-back boss fights, drawn and animated in the style of 1930s cartoons.',
 'Stunning hand-animated art|Challenging boss patterns|Local two-player co-op',
 'Very difficult|Mostly boss fights, little else', false],

['Dead Cells', 2018, 'Motion Twin', ['roguelike','action'], ['pc','playstation','xbox','switch','mobile'], 'S',
 [2,9,5,8,10], 'short', 20,
 'A roguelike action platformer where you fight through a changing island. Each run unlocks new weapons and paths for future attempts.',
 'Fluid, fast combat|Huge variety of weapons|Runs fit into short sessions',
 'Steep difficulty|Light story', false],

['Unpacking', 2021, 'Witch Beam', ['puzzle','simulation'], ['pc','playstation','xbox','switch'], 'S',
 [6,1,3,1,2], 'short', 4,
 'A calm puzzle game about taking things out of boxes and finding a place for them in a new home. The story is told entirely through the objects you unpack.',
 'Relaxing and satisfying|Clever wordless storytelling|No time pressure',
 'Short|Very little challenge', false],

['Spiritfarer', 2020, 'Thunder Lotus Games', ['simulation','adventure'], ['pc','playstation','xbox','switch'], 'SC',
 [8,1,6,1,3], 'medium', 30,
 'A cosy management game about death. As a ferrymaster to the deceased, you care for spirits on your boat before helping them move on.',
 'Emotional and gentle story|Relaxing building and crafting|Local co-op with a second player',
 'Can be emotionally heavy|Some tasks become repetitive', false],

['Phasmophobia', 2020, 'Kinetic Games', ['horror'], ['pc','playstation','xbox'], 'SC',
 [1,1,5,6,9], 'short', null,
 'A co-op ghost-hunting horror game. Use equipment to collect evidence and identify the type of ghost before it hunts your team.',
 'Very scary with friends|Investigation is different each round|Supports voice recognition',
 'Less fun solo|Not much story', false],

['Alan Wake 2', 2023, 'Remedy Entertainment', ['horror','adventure'], ['pc','playstation','xbox'], 'S',
 [9,6,5,5,3], 'long', 20,
 'A survival horror story told through two characters: an FBI agent investigating ritual murders and a writer trapped in a nightmare dimension.',
 'Ambitious, unusual storytelling|Strong atmosphere and presentation|Mind-bending moments',
 'Slow pace at times|Combat is limited', false],

['God of War', 2018, 'Santa Monica Studio', ['action','adventure'], ['pc','playstation'], 'S',
 [9,9,7,5,4], 'medium', 21,
 'Kratos and his son Atreus journey through the realms of Norse mythology. A heavy, close-up combat system and a personal father-son story.',
 'Powerful father-son story|Weighty, satisfying combat|Optional areas worth exploring',
 'Some puzzles slow the pace|Backtracking for collectables', false],

['Marvel\'s Spider-Man', 2018, 'Insomniac Games', ['action','adventure'], ['pc','playstation'], 'S',
 [8,8,7,3,4], 'medium', 17,
 'Swing across New York City as Peter Parker in an open-world superhero adventure with acrobatic combat and a story about Peter\'s life in and out of the suit.',
 'Web-swinging feels fantastic|Strong story|Approachable difficulty',
 'Side activities are repetitive|Some stealth sections are slow', false],

['Persona 5 Royal', 2020, 'Atlus', ['rpg'], ['pc','playstation','xbox','switch'], 'S',
 [9,7,4,4,6], 'long', 100,
 'A stylish Japanese RPG about high-school students who lead double lives as the Phantom Thieves. Balance school, friendships and turn-based dungeon crawling.',
 'Incredible style and soundtrack|Memorable friendships|Satisfying turn-based battles',
 'Extremely long|Slow opening hours', false],

['Monster Hunter: World', 2018, 'Capcom', ['action','rpg'], ['pc','playstation','xbox'], 'SC',
 [3,10,6,7,10], 'long', 47,
 'Track and hunt huge monsters in living ecosystems, then craft new weapons and armour from what you carve. Hunts can be played solo or with up to four players.',
 'Huge, impressive monster fights|Fourteen distinct weapon types|Great co-op',
 'Complicated systems for beginners|Gear grinding is the core loop', false],

['Deep Rock Galactic', 2020, 'Ghost Ship Games', ['shooter'], ['pc','playstation','xbox'], 'SC',
 [1,8,7,6,10], 'short', null,
 'A co-op first-person shooter for up to four dwarves. Dig through destructible caves, mine minerals and fight off alien swarms.',
 'Excellent teamwork-focused co-op|Destructible cave systems|Friendly community',
 'Best with others|Missions can feel repetitive over time', false],

['Tetris Effect: Connected', 2020, 'Monstars / Resonair', ['puzzle'], ['pc','playstation','xbox','switch'], 'SCM',
 [2,1,1,4,9], 'short', null,
 'Classic Tetris paired with music and visuals that react to every move, plus co-op and competitive multiplayer modes.',
 'Hypnotic music and visuals|Instantly familiar|Great for short sessions',
 'It is still Tetris at its core|Limited story', false],

['Undertale', 2015, 'Toby Fox', ['rpg'], ['pc','playstation','xbox','switch'], 'S',
 [9,4,5,4,8], 'short', 7,
 'A small RPG where you never have to kill anyone. Battles mix bullet-hell dodging with talking your way out, and the game remembers how you treat its characters.',
 'Funny and surprising writing|Memorable soundtrack|Choices are remembered',
 'Simple graphics|Short main route', false],

['Ori and the Will of the Wisps', 2020, 'Moon Studios', ['platformer','adventure'], ['pc','xbox','switch'], 'S',
 [7,6,8,6,4], 'medium', 12,
 'A beautiful Metroidvania platformer about a small forest spirit. Flowing movement, emotional moments and tense escape sequences.',
 'Gorgeous visuals and music|Smooth, satisfying movement|Emotional story',
 'Escape sequences can be frustrating|Limited replay value', false],

['Hitman 3', 2021, 'IO Interactive', ['action','puzzle'], ['pc','playstation','xbox'], 'S',
 [5,5,7,6,9], 'medium', 12,
 'Agent 47 takes on contracts in large sandbox levels. Each mission is a puzzle with many creative ways to reach your target.',
 'Huge, detailed levels|Many creative solutions|Highly replayable',
 'Story is secondary|Trial and error can be slow', false],

['Genshin Impact', 2020, 'HoYoverse', ['rpg','adventure'], ['pc','playstation','mobile'], 'SC',
 [6,7,9,3,8], 'medium', null,
 'A free-to-play open-world action RPG. Explore the world of Teyvat, switch between elemental characters and team up with friends in co-op.',
 'Large, beautiful open world|Free to play|Elemental team combat',
 'Gacha system for new characters|Daily resin limits progress', false],
],

/*
 * Demo accounts. Passwords are DEVELOPMENT ONLY — see README.
 * [username, email, role, password, bio]
 */
'users' => [
    ['admin',              'admin@gaminghub.local',     'admin', 'Admin@Dev2026', 'Site administrator (development account).'],
    ['demo_player',        'demo@gaminghub.local',      'user',  'Demo@2026',     'Mostly RPGs and cosy games. Always looking for the next story-heavy adventure.'],
    ['pixel_pilgrim',      'pixel@gaminghub.local',     'user',  'Player@2026',   'Metroidvanias and anything with a big map.'],
    ['lanternfox',         'lanternfox@gaminghub.local','user',  'Player@2026',   'Story first, combat second.'],
    ['savepoint_sam',      'sam@gaminghub.local',       'user',  'Player@2026',   'Short sessions after work.'],
    ['quiet_cartographer', 'cart@gaminghub.local',      'user',  'Player@2026',   'Exploration and puzzle games.'],
    ['combo_cat',          'combo@gaminghub.local',     'user',  'Player@2026',   'Fighting games and hard action games.'],
    ['nightowl_nik',       'nik@gaminghub.local',       'user',  'Player@2026',   'Late-night co-op with friends.'],
    ['tea_and_tetris',     'tea@gaminghub.local',       'user',  'Player@2026',   'Relaxing games only, please.'],
    ['grindstone',         'grind@gaminghub.local',     'user',  'Player@2026',   'Hundreds of hours in RPGs and hunting games.'],
    ['meadowbyte',         'meadow@gaminghub.local',    'user',  'Player@2026',   'Farming sims and strategy.'],
    ['rookie_ronin',       'ronin@gaminghub.local',     'user',  'Player@2026',   'New to PC gaming, trying everything.'],
    ['free_codes_4u',      'spam@gaminghub.local',      'user',  'Player@2026',   ''],
],

/*
 * Demo reviews: game title => [[username, rating, text, days ago], ...]
 * These are sample reviews written by the project team for demonstration.
 */
'reviews' => [
'Hollow Knight' => [
    ['pixel_pilgrim', 5, 'The map keeps opening up long after you think you have seen it all. Every new ability made me want to revisit old areas.', 40],
    ['rookie_ronin', 3, 'Beautiful, but I got lost a lot early on and some bosses took me dozens of tries. Worth it if you are patient.', 12],
    ['combo_cat', 5, 'Combat is simple on paper but the bosses demand real precision. Some of the best fights in the genre.', 75],
],
'Stardew Valley' => [
    ['tea_and_tetris', 5, 'My comfort game. I play a few in-game days before bed and it never stresses me out.', 30],
    ['meadowbyte', 5, 'Co-op farm with my sister has been the best part of our week for months.', 64],
    ['savepoint_sam', 4, 'Great for short sessions. The first season felt slow until I figured out sprinklers.', 8],
    ['demo_player', 5, 'I came for the farming and stayed for the townspeople. Their stories are surprisingly sweet.', 21],
],
'Celeste' => [
    ['combo_cat', 5, 'Every death is your own fault and respawns are instant, so it never feels unfair. The final chapter is incredible.', 50],
    ['lanternfox', 5, 'I did not expect a platformer to say something this honest about anxiety.', 33],
    ['rookie_ronin', 4, 'Turned on assist mode for a couple of screens and do not regret it. Tough but kind.', 5],
],
'Hades' => [
    ['savepoint_sam', 5, 'A run takes about half an hour, which is perfect for weeknights. The story moving forward after each death is genius.', 18],
    ['lanternfox', 5, 'Every character has so much dialogue that I kept dying on purpose to hear more.', 60],
    ['combo_cat', 5, 'Weapon aspects and boons combine in so many ways. Still finding new builds after 80 hours.', 90],
    ['rookie_ronin', 4, 'Hard at first, but you get stronger between runs so it never felt hopeless.', 3],
],
'The Witcher 3: Wild Hunt' => [
    ['lanternfox', 5, 'Side quests here are better than the main story in most games. The Bloody Baron questline stayed with me.', 100],
    ['grindstone', 4, 'Huge world and great quests, but the combat never fully clicked for me.', 45],
    ['demo_player', 5, 'Took me 90 hours with the expansions and I would happily do it again.', 14],
],
'Elden Ring' => [
    ['pixel_pilgrim', 5, 'Turning a corner and finding an entire hidden area never stopped being exciting.', 25],
    ['grindstone', 5, 'If a boss is too hard you can go somewhere else and come back stronger. That freedom changes everything.', 70],
    ['rookie_ronin', 3, 'Amazing world but I bounced off the difficulty a few times. Co-op summons saved me.', 9],
    ['combo_cat', 5, 'Brutal and fair. Some of the best boss design in any game.', 55],
],
'Portal 2' => [
    ['quiet_cartographer', 5, 'Puzzles that make you feel smart without ever explaining the solution. The writing is hilarious.', 120],
    ['nightowl_nik', 5, 'The co-op campaign is the reason to play. Lots of laughing and accidental portal deaths.', 80],
],
'Minecraft' => [
    ['nightowl_nik', 5, 'Our friend server has been running for two years. Nothing else gives this much freedom.', 35],
    ['tea_and_tetris', 4, 'Creative mode is my relaxation. Survival at night is a bit too tense for me.', 27],
    ['rookie_ronin', 4, 'Had no idea what to do at first. Once a friend showed me the basics I was hooked.', 2],
],
'It Takes Two' => [
    ['nightowl_nik', 5, 'Every chapter feels like a new game. Best co-op experience I have had.', 48],
    ['meadowbyte', 4, 'Gameplay variety is amazing. The story was a little cheesy but we still loved it.', 15],
],
'Red Dead Redemption 2' => [
    ['lanternfox', 5, 'Arthur Morgan is one of the best characters in gaming. The ending wrecked me.', 110],
    ['savepoint_sam', 3, 'Gorgeous, but everything is slow. Hard to make progress in a one-hour session.', 20],
    ['grindstone', 5, 'Spent hours just hunting and riding around. The world feels alive.', 66],
],
'Disco Elysium' => [
    ['lanternfox', 5, 'The best writing I have ever seen in a game. Failing checks is often more interesting than passing them.', 52],
    ['demo_player', 5, 'No combat at all and I never missed it. Every conversation is a skill check and a joke at the same time.', 11],
    ['combo_cat', 3, 'Impressive, but it is basically a novel. Not for me — I need some action.', 38],
],
'Rocket League' => [
    ['savepoint_sam', 4, 'Five-minute matches are perfect when I only have a little time.', 16],
    ['nightowl_nik', 5, 'Split-screen with friends is endless fun even when none of us can hit aerials.', 44],
    ['rookie_ronin', 3, 'Fun, but online players are much better than me. Big learning curve.', 6],
],
'Among Us' => [
    ['nightowl_nik', 5, 'With the right group of friends on voice chat this is pure chaos. With strangers, not so much.', 90],
    ['meadowbyte', 3, 'Great fun at parties, but you really need a group.', 58],
],
'Slay the Spire' => [
    ['meadowbyte', 5, 'Every run makes me think about my deck differently. Seeing enemy intentions makes it feel fair.', 37],
    ['savepoint_sam', 5, 'I can save mid-run and come back later. Perfect commute game on my phone.', 10],
    ['grindstone', 4, 'Very deep, though the later ascension levels get punishing.', 82],
],
'Baldur\'s Gate 3' => [
    ['grindstone', 5, 'My choices genuinely changed what happened. 120 hours and I want to start again with a different class.', 30],
    ['demo_player', 5, 'Played the whole campaign in co-op with a friend. Companions are wonderful.', 19],
    ['savepoint_sam', 3, 'Incredible game, but it needs long sessions. Turn-based fights take a while.', 7],
    ['lanternfox', 5, 'Dialogue and reactivity on another level. Felt like a real tabletop campaign.', 50],
],
'The Legend of Zelda: Breath of the Wild' => [
    ['quiet_cartographer', 5, 'See a mountain, climb the mountain. Exploration has never felt this free.', 140],
    ['pixel_pilgrim', 4, 'Loved the freedom. Weapon durability annoyed me until I learned to stop hoarding.', 63],
],
'Super Mario Odyssey' => [
    ['tea_and_tetris', 5, 'Pure joy. Every kingdom has so many surprises, and it never gets stressful.', 29],
    ['rookie_ronin', 4, 'Easy to get into. Played it with my younger brother controlling Cappy.', 13],
],
'Animal Crossing: New Horizons' => [
    ['tea_and_tetris', 5, 'Ten minutes every morning with my coffee. Exactly what I need.', 85],
    ['meadowbyte', 4, 'Lovely, but waiting a real day for buildings tests my patience.', 41],
],
'Sekiro: Shadows Die Twice' => [
    ['combo_cat', 5, 'When the deflect rhythm finally clicks it feels like a dance. Hardest game I have beaten and the most rewarding.', 72],
    ['rookie_ronin', 2, 'Stuck on the second big boss for a week. No difficulty options made me give up.', 4],
],
'Overwatch 2' => [
    ['nightowl_nik', 4, 'Great with a regular team. Solo queue can be frustrating.', 23],
    ['combo_cat', 3, 'Heroes are fun but balance changes happen so often that it is hard to keep up.', 49],
],
'Valorant' => [
    ['combo_cat', 4, 'Precise and tactical. Very rewarding once you learn the maps, very harsh before that.', 31],
    ['rookie_ronin', 3, 'Matches are long and mistakes are punished hard. Fun when it works.', 1],
],
'Fortnite' => [
    ['nightowl_nik', 4, 'Squads with friends on a Friday night is a tradition. Zero build mode helps a lot.', 17],
    ['savepoint_sam', 3, 'Fun in short bursts but the constant updates mean I never know what is going on.', 26],
],
'Outer Wilds' => [
    ['quiet_cartographer', 5, 'Go in blind. The moment everything comes together is something I will never forget.', 95],
    ['pixel_pilgrim', 5, 'No upgrades, just knowledge. One of the most original games ever made.', 54],
    ['rookie_ronin', 4, 'I got lost a few times but taking notes helped. Amazing once it clicked.', 11],
],
'Subnautica' => [
    ['quiet_cartographer', 5, 'The ocean is beautiful and terrifying. Building my first underwater base was magical.', 61],
    ['tea_and_tetris', 3, 'Lovely in shallow water but the deep parts were too scary for me.', 39],
],
'Terraria' => [
    ['nightowl_nik', 5, 'Hundreds of hours with friends. There is always another boss or biome to tackle.', 77],
    ['pixel_pilgrim', 4, 'So much content. I keep a wiki open the whole time though.', 34],
],
'Resident Evil 4' => [
    ['combo_cat', 5, 'Parrying a chainsaw with a knife never gets old. Excellent remake.', 28],
    ['lanternfox', 4, 'Better story than I remembered from the original. Tense without being too scary.', 46],
],
'Inside' => [
    ['quiet_cartographer', 5, 'Three hours of pure atmosphere. That ending...', 150],
    ['savepoint_sam', 4, 'Short enough to finish over two evenings. Some puzzles are clever.', 22],
],
'Journey' => [
    ['tea_and_tetris', 5, 'Meeting a stranger and travelling together without words was moving.', 130],
    ['lanternfox', 4, 'Short, but every minute is beautiful.', 87],
],
'Sid Meier\'s Civilization VI' => [
    ['meadowbyte', 5, 'I sat down at 8pm and suddenly it was 2am. Dangerous game.', 57],
    ['grindstone', 4, 'Endless replay value, though late-game turns get slow on big maps.', 105],
],
'XCOM 2' => [
    ['meadowbyte', 4, 'Losing a veteran soldier hurts. Tense decisions every turn.', 68],
    ['combo_cat', 3, 'Missing a 95% shot is pain. Great design but frustrating sometimes.', 99],
],
'Mario Kart 8 Deluxe' => [
    ['nightowl_nik', 5, 'The default party game at every get-together.', 42],
    ['tea_and_tetris', 4, 'Smart steering means anyone can join in. Lots of fun.', 36],
],
'Forza Horizon 5' => [
    ['savepoint_sam', 4, 'Jump in, do a couple of races, done. Mexico looks stunning.', 24],
    ['rookie_ronin', 4, 'Assist options made it easy for me as a non-racing fan.', 8],
],
'Street Fighter 6' => [
    ['combo_cat', 5, 'Modern controls got my friends into fighting games. Training mode is excellent.', 20],
    ['rookie_ronin', 3, 'Ranked is scary. World Tour was fun to learn the basics though.', 14],
],
'Super Smash Bros. Ultimate' => [
    ['nightowl_nik', 5, 'Eight-player chaos with friends is unbeatable.', 88],
    ['combo_cat', 4, 'Deep competitive game hiding under a party game.', 112],
],
'Cuphead' => [
    ['combo_cat', 5, 'Every boss is a pattern puzzle. Looks like a playable cartoon.', 73],
    ['savepoint_sam', 3, 'Gorgeous but I can only handle a few attempts before I need a break.', 32],
],
'Dead Cells' => [
    ['pixel_pilgrim', 5, 'Combat feels amazing. Every run unlocks something new.', 47],
    ['combo_cat', 4, 'Boss cells difficulty gets wild. Great action.', 59],
],
'Unpacking' => [
    ['tea_and_tetris', 5, 'The calmest game I own, and the story told through objects made me tear up.', 44],
    ['quiet_cartographer', 4, 'Short and sweet. Lovely little puzzles.', 66],
],
'Spiritfarer' => [
    ['tea_and_tetris', 5, 'A cosy game about saying goodbye. I cried several times.', 53],
    ['lanternfox', 4, 'Beautiful story. Some fetch tasks dragged a bit.', 71],
],
'Phasmophobia' => [
    ['nightowl_nik', 5, 'Screaming with friends in voice chat. Best co-op horror around.', 19],
    ['rookie_ronin', 3, 'Played alone and it was mostly confusing. Needs friends.', 3],
],
'Alan Wake 2' => [
    ['lanternfox', 5, 'Bold, strange and unforgettable. The musical chapter is a highlight.', 26],
    ['pixel_pilgrim', 3, 'Loved the atmosphere, but long stretches with little gameplay.', 48],
],
'God of War' => [
    ['lanternfox', 5, 'A surprisingly tender father-son story with heavy, satisfying combat.', 102],
    ['grindstone', 4, 'Great combat and realms to explore. Some puzzles slowed me down.', 81],
],
'Marvel\'s Spider-Man' => [
    ['rookie_ronin', 5, 'Swinging around the city never gets old. Easy to enjoy even as a beginner.', 15],
    ['savepoint_sam', 4, 'Easy to jump in for a mission or two. Side content repeats itself.', 41],
],
'Persona 5 Royal' => [
    ['demo_player', 5, 'Over 100 hours and I was sad when it ended. The style and music are unmatched.', 27],
    ['savepoint_sam', 3, 'Loved it but the opening hours are very slow and it is massive.', 62],
],
'Monster Hunter: World' => [
    ['grindstone', 5, 'Four friends, one huge monster, perfect evening. I have 400 hours in this.', 93],
    ['rookie_ronin', 3, 'Tutorials did not explain enough. Much better once a friend helped.', 7],
],
'Deep Rock Galactic' => [
    ['nightowl_nik', 5, 'Rock and stone! The best co-op shooter for a group of friends.', 22],
    ['pixel_pilgrim', 4, 'Solo with Bosco is fine, but this shines with a full team.', 50],
],
'Tetris Effect: Connected' => [
    ['tea_and_tetris', 5, 'I get into a trance with the music. Perfect for unwinding.', 16],
    ['savepoint_sam', 4, 'Fifteen minutes of zone mode and my day feels better.', 9],
],
'Undertale' => [
    ['lanternfox', 5, 'Funny, surprising and kind. Remembering my choices across saves blew my mind.', 115],
    ['quiet_cartographer', 4, 'Short but packed with personality.', 98],
],
'Ori and the Will of the Wisps' => [
    ['pixel_pilgrim', 5, 'Movement flows beautifully. One of the prettiest games I have played.', 69],
    ['tea_and_tetris', 3, 'Gorgeous, but the escape sequences were too stressful for me.', 85],
],
'Hitman 3' => [
    ['quiet_cartographer', 5, 'Every level is a giant puzzle box. I replay Dartmoor constantly.', 43],
    ['grindstone', 4, 'Endless challenges to complete. Story is just background.', 74],
],
'Genshin Impact' => [
    ['grindstone', 4, 'Exploration is fantastic for a free game. Ignore the gacha and it is great.', 35],
    ['savepoint_sam', 3, 'Daily resin limit means I just log in for 20 minutes. Works for me sometimes.', 12],
    ['free_codes_4u', 1, 'FREE PRIMOGEMS!!! visit my channel for codes, best website, click now', 2],
],
],

/* Helpful votes are generated pseudo-randomly in build_sql.php. */

/* Reports for the admin moderation demo: [reporter, game title, review author, reason, details] */
'reports' => [
    ['tea_and_tetris', 'Genshin Impact', 'free_codes_4u', 'spam', 'Advertising, not a review.'],
    ['grindstone', 'Genshin Impact', 'free_codes_4u', 'spam', ''],
],

/* Wishlist for the demo account. */
'wishlist' => [
    'demo_player' => ['Outer Wilds', 'Hades', 'Celeste', 'Spiritfarer'],
],

/* Saved quiz preferences for the demo account. */
'preferences' => [
    'demo_player' => ['genre' => 'rpg', 'mood' => 'story', 'play_style' => 'solo', 'difficulty' => 'balanced',
                      'session' => 'long', 'focus' => 'story', 'platform' => 'pc'],
],
'user_genres'    => ['demo_player' => ['rpg', 'simulation', 'adventure']],
'user_platforms' => ['demo_player' => ['pc', 'switch']],
];
