<?php

declare(strict_types=1);

namespace MauticPlugin\BgeBundle\EventListener;

use Mautic\FormBundle\Model\FormModel;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UnifiedFormResultsReportSubscriber implements EventSubscriberInterface
{
    public const CONTEXT = 'bge.form.results.unified';
    private const VIEW_NAME = 'bge_form_results_union_4_11';

    public function __construct(
        private FormModel $formModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_BUILD    => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE => ['onReportGenerate', 0],
        ];
    }

    public function onReportBuilder(ReportBuilderEvent $event): void
    {
        if (!$event->checkContext([self::CONTEXT])) {
            return;
        }

        $columns = [
            'ufr.submission_id' => [
                'label' => 'Submission ID',
                'type'  => 'int',
            ],
            'ufr.form_id' => [
                'label' => 'Form ID',
                'type'  => 'int',
            ],
            'ufr.form_alias' => [
                'label' => 'Form Alias',
                'type'  => 'string',
            ],
            'ufr.locale' => [
                'label' => 'Locale',
                'type'  => 'string',
            ],
            'ufr.question_key' => [
                'label' => 'Question Key',
                'type'  => 'string',
            ],
            'ufr.question_label' => [
                'label' => 'Question Label',
                'type'  => 'string',
            ],
            'ufr.answer_value' => [
                'label' => 'Answer Value',
                'type'  => 'text',
            ],
            'ufr.submitted_at' => [
                'label'          => 'Submitted At',
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(ufr.submitted_at)',
            ],
            'ufr.contact_id' => [
                'label' => 'mautic.lead.report.contact_id',
                'type'  => 'int',
                'link'  => 'mautic_contact_action',
            ],
        ];

        $filters = [
            'ufr.form_id' => [
                'label'     => 'Form ID',
                'type'      => 'select',
                'list'      => $this->getFormFilterList(),
                'operators' => [
                    'eq'  => 'mautic.core.operator.equals',
                    'neq' => 'mautic.core.operator.notequals',
                ],
            ],
            'ufr.locale' => [
                'label' => 'Locale',
                'type'  => 'text',
            ],
        ];

        $data = [
            'display_name' => 'BGE Form Results Union 4-11',
            'columns'      => array_merge($columns, $event->getLeadColumns('l.')),
            'filters'      => $filters,
        ];

        $event->addTable(self::CONTEXT, $data, 'forms');
    }

    public function onReportGenerate(ReportGeneratorEvent $event): void
    {
        if (!$event->checkContext([self::CONTEXT])) {
            return;
        }

        $qb = $event->getQueryBuilder();
        $qb->from(self::VIEW_NAME, 'ufr');
        $event->applyDateFiltersWithoutNullValues($qb, 'submitted_at', 'ufr');

        if ($event->usesColumnWithPrefix('l')) {
            $qb->leftJoin('ufr', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = ufr.contact_id');
        }

        $event->setQueryBuilder($qb);
    }

    private function getFormFilterList(): array
    {
        $list  = [];
        $forms = $this->formModel->getEntities(
            [
                'filter' => [
                    'force' => [
                        [
                            'column' => 'f.id',
                            'expr'   => 'in',
                            'value'  => [4, 5, 6, 7, 8, 9, 10, 11],
                        ],
                    ],
                ],
                'limit' => 0,
            ]
        );

        foreach ($forms as $form) {
            if (is_object($form) && method_exists($form, 'getId') && method_exists($form, 'getName')) {
                $formId   = $form->getId();
                $formName = $form->getName();
            } elseif (is_array($form)) {
                $formId   = isset($form['id']) ? (int) $form['id'] : null;
                $formName = $form['name'] ?? (string) $formId;
            } else {
                continue;
            }

            if (empty($formId)) {
                continue;
            }

            $list[(string) $formId] = sprintf('%d - %s', $formId, (string) $formName);
        }

        if (empty($list)) {
            return [
                '4'  => '4',
                '5'  => '5',
                '6'  => '6',
                '7'  => '7',
                '8'  => '8',
                '9'  => '9',
                '10' => '10',
                '11' => '11',
            ];
        }

        return $list;
    }
}
