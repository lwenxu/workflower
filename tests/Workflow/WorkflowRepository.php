<?php
/*
 * Copyright (c) 2015 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace PHPMentors\Workflower\Workflow;

use PHPMentors\DomainKata\Entity\EntityInterface;

class WorkflowRepository implements WorkflowRepositoryInterface
{
    /**
     * @var array
     */
    private $workflows = array();

    public function __construct()
    {
        $this->add($this->createLoanRequestProcess());
    }

    /**
     * {@inheritDoc}
     */
    public function add(EntityInterface $entity)
    {
        assert($entity instanceof Workflow);

        $this->workflows[$entity->getId()] = $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(EntityInterface $entity)
    {
        assert($entity instanceof Workflow);
    }

    /**
     * {@inheritDoc}
     */
    public function findById($id)
    {
        if (!array_key_exists($id, $this->workflows)) {
            return null;
        }

        return $this->workflows[$id];
    }

    /**
     * @return Workflow
     */
    private function createLoanRequestProcess()
    {
        $workflowBuilder = new WorkflowBuilder();
        $workflowBuilder->setWorkflowId('LoanRequestProcess');
        $workflowBuilder->setWorkflowName('Loan Request Process');
        $workflowBuilder->addRole('ROLE_BRANCH', 'Branch');
        $workflowBuilder->addRole('ROLE_CREDIT_FACTORY', 'Credit Factory');
        $workflowBuilder->addRole('ROLE_BACK_OFFICE', 'Back Office');
        $workflowBuilder->addStartEvent('Start', 'ROLE_BRANCH');
        $workflowBuilder->addTask('RecordLoanApplicationInformation', 'ROLE_BRANCH', 'Record Loan Application Information');
        $workflowBuilder->addTask('CheckApplicantInformation', 'ROLE_BRANCH', 'Check Applicant Information', 'CheckApplicantInformation.LoanStudy');
        $workflowBuilder->addTask('LoanStudy', 'ROLE_CREDIT_FACTORY', 'Loan Study');
        $workflowBuilder->addTask('InformRejection', 'ROLE_CREDIT_FACTORY', 'Inform Rejection');
        $workflowBuilder->addTask('Disbursement', 'ROLE_BACK_OFFICE', 'Disbursement');
        $workflowBuilder->addExclusiveGateway('ApplicaionApproved', 'ROLE_CREDIT_FACTORY', 'Applicaion Approved?', 'ApplicaionApproved.Disbursement');
        $workflowBuilder->addEndEvent('End', 'ROLE_CREDIT_FACTORY');
        $workflowBuilder->addSequenceFlow('Start', 'RecordLoanApplicationInformation', 'Start.RecordLoanApplicationInformation');
        $workflowBuilder->addSequenceFlow('RecordLoanApplicationInformation', 'CheckApplicantInformation', 'RecordLoanApplicationInformation.CheckApplicantInformation');
        $workflowBuilder->addSequenceFlow('CheckApplicantInformation', 'LoanStudy', 'CheckApplicantInformation.LoanStudy', 'Ok');
        $workflowBuilder->addSequenceFlow('CheckApplicantInformation', 'End', 'CheckApplicantInformation.End', 'Rejected', 'rejected === true');
        $workflowBuilder->addSequenceFlow('LoanStudy', 'ApplicaionApproved', 'LoanStudy.ApplicaionApproved');
        $workflowBuilder->addSequenceFlow('ApplicaionApproved', 'Disbursement', 'ApplicaionApproved.Disbursement', 'Ok');
        $workflowBuilder->addSequenceFlow('ApplicaionApproved', 'InformRejection', 'ApplicaionApproved.InformRejection', 'Rejected', 'rejected === true');
        $workflowBuilder->addSequenceFlow('InformRejection', 'End', 'InformRejection.End');
        $workflowBuilder->addSequenceFlow('Disbursement', 'End', 'Disbursement.End');

        return $workflowBuilder->build();
    }
}