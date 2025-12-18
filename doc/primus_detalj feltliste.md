
#frmNMMPrimus felt-liste                                                                                   

Radio-kontrollene kan ha følgende valg med tilhørende verdier (kalt variabelen 'iCh')
- "Ingen" iCh = 1
- "Fotografi" iCh = 2
- "Samling" iCh = 3
- "Foto+Saml" iCh = 4
- "Annet" iCh = 5
- "Alle" iCh = 6
Check Box: Aksesjon
nmmfoto-field:    Aksesjon          Visible:          False

Text Box: Avbildet
nmmfoto-field:    Avbildet          Visible:          True

Text Box: Bilde_Fil
nmmfoto-field:    Bilde_Fil         Visible:          True

Text Box: Foto_ID
nmmfoto-field:    Foto_ID           Visible:          False

Text Box: FotoFirma
nmmfoto-field:    FotoFirma         Visible:          If iCh=2,4,6 True, Else False

Text Box: Fotograf
nmmfoto-field:    Fotograf          Visible:          If iCh=2,4,6 True, Else False
DefaultValue:     "10F:"
                               
Check Box: Fotografi								  
nmmfoto-field:    Fotografi         Visible:          False

Text Box: FotoSted
nmmfoto-field:    FotoSted          Visible:          If iCh=2,4,6 True, Else False

Text Box: FotoTidFra
nmmfoto-field:    FotoTidFra        Visible:          If iCh=2,4,6 True, Else False

Text Box: FotoTidTil
nmmfoto-field:    FotoTidTil        Visible:          If iCh=2,4,6 True, Else False

Check Box: FriKopi
nmmfoto-field:    FriKopi           Visible:          True
Default:          If iCh=3,4,6 False, Else True

Text Box: FTO
nmmfoto-field:=[NMM_ID].[Column](6) Visible:          True

Text Box: Hendelse
nmmfoto-field:    Hendelse          Visible:          True

Text Box: MotivBeskr
nmmfoto-field:    MotivBeskr        Visible:          True

Text Box: MotivBeskrTillegg
nmmfoto-field:    MotivBeskrTillegg Visible:          True

Text Box: MotivEmne
nmmfoto-field:    MotivEmne         Visible:          True

Text Box: MotivKriteria
nmmfoto-field:    MotivKriteria     Visible:          True  

Text Box: MotivType
nmmfoto-field:    MotivType         Visible:          True

Combo Box: NMM_ID
nmmfoto-field:    NMM_ID            Visible:          False 
RowSource:        nmm_skip        						RowSourceTyp      table
LimitToList:      True

Combo Box: NMMSerie
nmmfoto-field:    -                 Visible:          True
DefaultValue:     "-"  
RowSource:        bildeserie        					RowSourceTyp      table 
LimitToList:      True

Text Box: Plassering
nmmfoto-field:    Plassering        Visible:          True

Text Box: Prosess
nmmfoto-field:    Prosess           Visible:          True

Text Box: ReferFArk
nmmfoto-field:    ReferFArk         Visible:          True

Text Box: ReferNeg
nmmfoto-field:    ReferNeg          Visible:          True

Combo Box: Samling
nmmfoto-field:    Samling			Visible:          If iCh=3,4,6 True, Else False
DefaultValue:     "C2-Johnsen, Per-Erik"  
RowSource:        C2-Johnsen, Per-Erik;Gjersøe, Georg;-        RowSourceTyp      Value List 
LimitToList:      True

Text Box: SerNr
nmmfoto-field:    SerNr             Visible:          True

Text Box: Status
nmmfoto-field:    Status            Visible:          True 

Combo Box: Svarthvitt
nmmfoto-field:    Svarthvitt        Visible:          True  
RowSource:        Svart-hvit;Farge;Svart-hvit         RowSourceTyp      Value List
                  håndkolorert;Tonet
LimitToList:      True


Text Box: Text80
nmmfoto-field:    =[NMM_ID].[Column](2) & " " &       Visible:          True
                  [NMM_ID].[Column](1)
				  
Combo Box: Tilstand
nmmfoto-field:    Tilstand          Visible:          True
RowSource:        God;Dårlig;-                        RowSourceTyp      Value List 
LimitToList:      True                              

Text Box: URL_Bane
nmmfoto-field:    URL_Bane          Visible:          False

Text Box: URL_Bane_Bilde            
nmmfoto-field: -   					Visible: 		  False                               

Text Box: UUID
nmmfoto-field:    UUID              Visible:          False
