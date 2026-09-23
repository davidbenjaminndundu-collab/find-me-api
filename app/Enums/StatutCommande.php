<?php

namespace App\Enums;

enum StatutCommande: string
{
    case Brouillon = 'brouillon';
    case Envoyee = 'envoyee';
    case EnAttenteReponse = 'en_attente_reponse';
    case InformationsDemandees = 'informations_demandees';
    case Acceptee = 'acceptee';
    case Refusee = 'refusee';
    case OffreProposee = 'offre_proposee';
    case OffreAcceptee = 'offre_acceptee';
    case OffreExpiree = 'offre_expiree';
    case EnAttentePaiement = 'en_attente_paiement';
    case PaiementEnCours = 'paiement_en_cours';
    case Payee = 'payee';
    case EnCoursRealisation = 'en_cours_realisation';
    case Livree = 'livree';
    case RevisionDemandee = 'revision_demandee';
    case EnRevision = 'en_revision';
    case EnLitige = 'en_litige';
    case Terminee = 'terminee';
    case Annulee = 'annulee';
    case Remboursee = 'remboursee';
}