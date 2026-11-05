"""BOCA scoring used by the local Animeitor adapter and parity tests."""
from collections import defaultdict


def standings(teams, runs, penalty_minutes):
    results = {}
    for team in teams:
        results[team['id']] = {'team': team['id'], 'solved': 0, 'penalty': 0, 'last': 0,
                               'number': int(team['number']), 'site': int(team['site'])}
    grouped = defaultdict(list)
    for run in runs:
        if run['team'] not in results:
            raise ValueError('Run pertence a equipe ausente')
        if run['answer'] not in ('Y', 'N', 'X', '?'):
            raise ValueError('Veredito desconhecido')
        grouped[(run['team'], run['problem'])].append(run)
    for (team, _problem), entries in grouped.items():
        wrong = 0
        for run in sorted(entries, key=lambda r: (r['seconds'], r['id'])):
            if run['answer'] == 'N':
                wrong += 1
            elif run['answer'] == 'Y':
                minute = run['seconds'] // 60
                results[team]['solved'] += 1
                results[team]['penalty'] += minute + wrong * penalty_minutes
                results[team]['last'] = max(results[team]['last'], minute)
                break
    return sorted(results.values(), key=lambda r: (-r['solved'], r['penalty'], r['last'], r['number'], r['site']))


def public_rows(rows):
    return [{key: row[key] for key in ('team', 'solved', 'penalty')} for row in rows]
