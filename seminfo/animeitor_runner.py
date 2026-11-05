#!/usr/bin/env python3
"""Adapter for the pinned GPL Animeitor. Upstream files remain untouched."""
import json
import os
from pathlib import Path
import sys

HERE = Path(__file__).resolve().parent
VENDOR = HERE / 'vendor' / 'maratona-animeitor'


def main():
    folder = Path(sys.argv[1]).resolve()
    metadata = json.loads((folder / 'metadata.json').read_text(encoding='utf-8'))
    if not metadata.get('final') or not metadata.get('parity_verified'):
        raise ValueError('Apenas exportacoes finais conferidas podem ser projetadas')
    sys.path.insert(0, str(VENDOR))
    os.chdir(VENDOR)
    import Contest as upstream
    import builtins
    # Upstream uses the platform default encoding; Windows otherwise corrupts
    # Portuguese team/contest names from a UTF-8 webcast.
    def utf8_open(path, mode='r', **kwargs):
        if 'b' not in mode: kwargs.setdefault('encoding', 'utf-8')
        return builtins.open(path, mode, **kwargs)
    upstream.open = utf8_open
    from scoring import standings

    class EventContest(upstream.Contest):
        def load_photos(self):
            # Photos are optional; never stop a presentation for an input prompt.
            self.teamPhotos = {}

        def load_runs(self):
            self.runList, self.blindRunList = [], []
            for line in (Path(self.baseDir) / 'runs').read_text(encoding='utf-8').split('\n'):
                if not line:
                    continue
                number, minute, team, problem, verdict = line.split('\x1c')
                if verdict not in ('Y','N','X','?'):
                    raise ValueError('Veredito desconhecido')
                minute = int(minute)
                if self.revealUntil is not None and minute > self.revealUntil:
                    continue
                run = upstream.RunTuple(int(number), minute, team, ord(problem)-65, verdict)
                if minute >= self.blindTime:
                    self.blindRunList.append(run)
                    self.runList.append(run._replace(answer='?'))
                else:
                    self.runList.append(run)
            self.runList.sort(key=lambda r: (r.time, r.id))

        def calc_details(self):
            self.teamDetails = {}
            for team in self.teamMap:
                details = [(0,0,None)] * self.numProblems
                for run in sorted((r for r in self.runList if r.team==team), key=lambda r:(r.time,r.id)):
                    errors, pending, solved = details[run.prob]
                    if solved is not None:
                        continue
                    if run.answer == 'N': errors += 1
                    elif run.answer == '?': pending += 1
                    elif run.answer == 'Y': solved = run.time
                    details[run.prob] = (errors,pending,solved)
                self.teamDetails[team] = details

        def calc_summary(self):
            self.calc_details()
            self.teamSummary = {}
            for team, details in self.teamDetails.items():
                solved = sum(1 for errors,pending,minute in details if minute is not None)
                penalty = sum(minute + errors*self.penaltyTime for errors,pending,minute in details if minute is not None)
                last = max((minute for errors,pending,minute in details if minute is not None), default=0)
                self.teamSummary[team] = (solved,penalty,last)

        def rank_teams(self):
            if self.teamRanking:
                return self.teamRanking
            self.calc_summary()
            teams = [t for t in metadata['teams'] if t['id'] in self.teamMap]
            runs = [{'id': r.id, 'seconds':r.time*60,'team':r.team,'problem':chr(r.prob+65),'answer':r.answer} for r in self.runList]
            # __init__ calls rank_teams before the contest header has been read.
            rows = standings(teams,runs,getattr(self,'penaltyTime',20))
            self.teamRanking = [(i+1,row['team']) for i,row in enumerate(rows)]
            return self.teamRanking

    upstream.Contest = EventContest
    if '--smoke-test' in sys.argv:
        from argparse import Namespace
        from Handler import Handler
        from ScoreboardHandler import ScoreboardHandler
        import pygame
        import util
        contest=EventContest(Namespace(webcast_dir=str(folder),probs_ajust=0,select_teams=[],remove_teams=[]))
        contest.load_data()
        Handler.contest=contest
        util.init_pygame(True)
        handler=ScoreboardHandler()
        handler.tick()
        pygame.image.save(util.get_screen(),str(folder/'frozen.png'))
        for _ in range(len(contest.blindRunList)+1):
            contest.rank_teams();contest.oldTeamRanking=contest.teamRanking
            contest.unblind_runs();contest.teamRanking=None
        rows=[]
        for rank,team in contest.rank_teams():
            solved,penalty,last=contest.teamSummary[team]
            rows.append({'team':team,'solved':solved,'penalty':penalty})
        if rows != metadata['standings']:
            raise AssertionError('Adaptador diverge depois da revelacao')
        handler.setup_animation();handler.tick()
        pygame.image.save(util.get_screen(),str(folder/'revealed.png'))
        pygame.quit()
        print('PASSOU: Animeitor renderizado, freeze e resultado final conferidos.')
        return
    import main as presentation
    sys.argv = [str(VENDOR / 'main.py'), str(folder)]
    presentation.main()


if __name__ == '__main__':
    main()
