import base64
import json
from pathlib import Path
import sys
import tempfile
import unittest
from unittest.mock import patch

sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
import manage as kit
import problems


class ReferenceTests(unittest.TestCase):
    def setUp(self):
        self.tmp=tempfile.TemporaryDirectory()
        self.patches=[patch.object(kit,'LOCAL',Path(self.tmp.name)),
                      patch.object(kit,'private_directory',lambda p:p.mkdir(parents=True,exist_ok=True)),
                      patch.object(kit,'source_hash',return_value='server-v1')]
        for p in self.patches:p.start()
        self.problem={'number':1,'name':'A','title':'Soma','basename':'A','package_hash':'hash-v1','autojudge':1023,'updated_at':1}
        self.problem['revision']='a'*64
        self.sources=[{'name':'Main.c','content':base64.b64encode(b'int main(){return 0;}').decode()}]
        self.data={'problem':1,'revision':'a'*64,'sources':self.sources}

    def tearDown(self):
        for p in reversed(self.patches):p.stop()
        self.tmp.cleanup()

    def test_reference_input_rejects_paths_duplicates_binaries_large_files_and_forged_numbers(self):
        invalid=[self.data|{'problem':True},self.data|{'revision':'bad'},self.data|{'sources':[]},
                 self.data|{'sources':self.sources*2},self.data|{'sources':[{'name':'../x.c','content':'YQ=='}]},
                 self.data|{'sources':[{'name':'X.py','content':'AA=='}]},
                 self.data|{'sources':[{'name':'X.c','content':base64.b64encode(b'x'*(problems.MAX_SOURCE+1)).decode()}]}]
        for data in invalid:
            with self.assertRaises(ValueError):problems.parse_sources(data)
        number,revision,sources=problems.parse_sources(self.data|{'sources':[{'name':'X.py','content':'cHJpbnQoNDIp'}]})
        self.assertEqual(sources[0]['name'],'X.py3')
        self.assertEqual(sources[0]['lang'],5)

    def test_partial_stale_failed_or_wrong_category_reports_never_approve_review(self):
        path=problems.report_path('si',1)
        kit.write_json(path,{'profile':'si','revision':'a'*64,'server_source_sha256':'server-v1','passed':True,'complete':True})
        with patch.object(problems,'catalog',return_value=[self.problem]):
            self.assertEqual(problems.assert_reviewable('si'),problems.digest([self.problem]))
            for change in ({'complete':False},{'passed':False},{'revision':'b'*64},{'profile':'tii'},{'server_source_sha256':'old'}):
                kit.write_json(path,{'profile':'si','revision':'a'*64,'server_source_sha256':'server-v1','passed':True,'complete':True}|change)
                with self.assertRaises(ValueError):problems.assert_reviewable('si')

    def test_validation_rejects_stale_packages_before_starting_judge(self):
        with patch('organizer.editable'),patch.object(problems,'catalog',return_value=[self.problem]),patch.object(problems,'isolated_judge') as judge:
            with self.assertRaises(ValueError):problems.validate('si',self.data|{'revision':'b'*64})
            judge.assert_not_called()

    def test_validation_is_private_partial_and_retires_success_on_failed_retry(self):
        payload={'revision_data':{k:v for k,v in self.problem.items() if k!='revision'},'package':'UEs='}
        results=[{'name':'Main.c','language':'C','accepted':True,'verdict':'YES'}]
        with patch('organizer.editable'),patch.object(problems,'catalog',return_value=[self.problem]),patch.object(kit,'control',return_value=payload),patch.object(problems,'isolated_judge',return_value=results):
            report=problems.validate('si',self.data)
        self.assertTrue(report['passed']);self.assertFalse(report['complete'])
        self.assertEqual(report['missing_languages'],['C++20','Java','Python3'])
        self.assertNotIn('content',json.dumps(problems.overview('si',[self.problem])))
        self.assertFalse(problems.report_path('tii',1).exists())
        with patch('organizer.editable'),patch.object(problems,'catalog',return_value=[self.problem]),patch.object(kit,'control',return_value=payload),patch.object(problems,'isolated_judge',side_effect=RuntimeError('judge unavailable')):
            with self.assertRaises(RuntimeError):problems.validate('si',self.data)
        self.assertFalse(problems.read_report('si',self.problem)['passed'])

    def test_backup_reference_checksums_exclude_temporary_passwords_and_detect_tampering(self):
        root=Path(self.tmp.name)/'backup'
        kit.write_json(root/'reference-solutions/1/sources.json',{'sources':self.sources})
        (root/'reference-solutions/1/private.env').write_text('temporary judge password')
        files=kit.reference_files(root/'reference-solutions')
        self.assertEqual(set(files),{'1/sources.json'})
        self.assertEqual(kit.check_reference_backup(root,{'reference_files_sha256':files}),files)
        kit.write_json(root/'reference-solutions/1/sources.json',{'sources':[]})
        with self.assertRaises(ValueError):kit.check_reference_backup(root,{'reference_files_sha256':files})


if __name__=='__main__':unittest.main()
