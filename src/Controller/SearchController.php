<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

class SearchController extends AppController
{
    
    public function isAuthorized($user = null)
    {
        return true;
    }

    public function quicksearch()
    {
        $this->Contacts = TableRegistry::get('Contacts');
        if ($this->request->getQuery('term')[0] == '^') {
            $this->request = $this->request->withQueryParams(['term' => substr($this->request->getQuery('term'), 1)]);
        } else {
            $this->request = $this->request->withQueryParams(['term' => '%' . $this->request->getQuery('term')]);
        }
        //TODO find only accessible
        $query = $this->Contacts->find()
            ->select(['id', 'contactname', 'legalname', 'email', 'phone', 'birth', 'workplace', 'comment'])
            ->where(['contactname LIKE "'.$this->request->getQuery('term').'%"'])
            ->orWhere(['legalname LIKE "'.$this->request->getQuery('term').'%"'])
            ->orWhere(['email LIKE "'.$this->request->getQuery('term').'%"'])
            ->orWhere(['phone LIKE "'.$this->request->getQuery('term').'%"'])
            ->orWhere(['comment LIKE "'.$this->request->getQuery('term').'%"'])
            ->orWhere(['workplace LIKE "'.$this->request->getQuery('term').'%"'])
            ->limit(25);
        foreach ($query as $row) {
            $label = '';
            if ($this->Contacts->isAccessible($row->id, $this->Auth->user('id'))) {
                if ($this->createHighlight($row->contactname) || $this->createHighlight($row->legalname)) {
                    $label .= '<span class="noaccess">';
                        $label .= $this->createHighlight($row->contactname) ? '♥ ' . $this->createHighlight($row->contactname) . ' ' : '';
                        $label .= $this->createHighlight($row->legalname) ? '♥ ' . $this->createHighlight($row->legalname) . ' ' : '';
                    $label .= '</span>';
                }
                if ($row->contactname) {
                    $label .= $this->createHighlight($row->contactname) ? '♥ ' . $this->createHighlight($row->contactname) . ' ' : '♥ ' . $row->contactname . ' ';
                }
                if ($row->legalname) {
                    $label .= $this->createHighlight($row->legalname) ? '♥ ' . $this->createHighlight($row->legalname) . ' ' : '♥ ' . $row->legalname . ' ';
                }
                $label .= $this->createHighlight($row->email) ? '✉ ' . $this->createHighlight($row->email) . ' ' : '';
                $label .= $this->createHighlight($row->phone) ? '☏ ' . $this->createHighlight($row->phone) . ' ' : '';
                $label .= (isset($row->birth) && $this->createHighlight($row->birth->format('Y-m-d'))) ? '↫ ' . $this->createHighlight($row->birth->format('Y-m-d')) . ' ' : '';
                $label .= $this->createHighlight($row->workplace) ? '♣ ' . $this->createHighlight($row->workplace) . ' ' : '';
                $label .= $this->createHighlight($row->comment) ? '✍ ' : '';
            }
            if ($label) {
                $result[] = [
                    'value' => 'c'.$row->id,
                    'label' => $label
                ];
            }
        }

        //groups
        $this->Groups = TableRegistry::get('Groups');
        $query = $this->Groups->find(
            'accessible',
            [
                'User.id' => $this->Auth->user('id'),
                'shared' => true
            ]
        )
            ->where(['name LIKE "'.$this->request->getQuery('term').'%"']);
        foreach ($query as $row) {
            if ($this->createHighlight($row->name)) {
                $label = '⁂ ' . $this->createHighlight($row->name);
                $result[] = [
                    'value' => 'g' . $row->id,
                    'label' => $label
                ];
            }
        }
        
        //skills
        $this->Skills = TableRegistry::get('Skills');
        $query = $this->Skills
            ->find()
            ->where(['name LIKE "'.$this->request->getQuery('term').'%"']);
        //debug($query->toArray());die();
        foreach ($query as $row) {
            if ($this->createHighlight($row->name)) {
                $label = '✄ ' . $this->createHighlight($row->name);
                $result[] = [
                    'value' => 's' . $row->id,
                    'label' => $label
                ];
            }
        }

        //histories
        //too many entries
        /*$this->Histories = TableRegistry::get('Histories');
        $query = $this->Histories->find()
                ->select(['id', 'detail'])
                ->where(['detail LIKE "'.$this->request->query('term').'%"']);
        foreach($query as $row) {
            $label = '⚑ ' . $this->createHighlight($row->detail);
            $result[] = array('value' => $row->id,
                              'label' => $label);
        }*/
        
        $this->set('result', $result);
        $this->set('_serialize', 'result');
    }

    private function createHighlight($value = null)
    {
        //sql returns ékezetes, but this one not
        $highlight = ['format' => '<span class="b i">\1</span>'];
        //remove % from beginning
        $term = $this->request->getQuery('term');
        if (strpos($value, '%') === 0) {
            $value = substr($value, 1);
        }
        if (strpos($term, '%') === 0) {
            $term = substr($term, 1);
        }
        if ($value && mb_strpos(mb_strtolower($value), $term) !== false) {
            return Text::highlight($value, $term, $highlight);
        } else {
            return null;
        }
    }
}
