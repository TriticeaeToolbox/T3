<?php
/**
 * Practical Haplotype Graph (PHG) project
 */
require 'config.php';
require $config['root_dir'].'includes/bootstrap.inc';
require $config['root_dir'].'theme/admin_header2.php';

?>
<h1>Practical Haplotype Graph (PHG) Project</h1>

This is a listing of sequence resources used to create a PHG wheat database.<br>
The fastq and GVCF files are stored in the Cornell BioHPC Cloud which is accessible using <a href="https://app.globus.org/file-manager?origin_id=940c21fe-377d-11e8-b96a-0ac6873fc732&origin_path=%2Fhome%2Fjj332_pgh%2F">Globus Online</a>.<br>
The PHG is described at <a href="https://bitbucket.org/bucklerlab/practicalhaplotypegraph/wiki/Home" target="_new">PHG Wiki</a>.<br>
The Wheat PHG is described at <a href="https://github.com/TriticeaeToolbox/PHG" target="_new">T3 Wheat PHG</a><br><br>

<table>
<tr><td>Description<td>Download Link<td>Reference
<tr><td>67 fastq from Kansas State Univ<br>
genotyping-by-sequencing (GBS) and exome capture on Illumina HiSeq 2000
<td>(EBI - ENA) Study: <a href="https://www.ebi.ac.uk/ena/data/view/PRJNA227449" target="_blank">PRJNA227449</a>, 
<a href="phg/PRJNA227449.txt">Meta data</a>
<td><a href="https://genomebiology.biomedcentral.com/articles/10.1186/s13059-015-0606-4" target="_blank">Haplotype map of allohexaploid wheat</a>

<tr><td>298 fastq from Kansas State Univ<br>
genotyping-by-sequencing (GBS) on Illumina HiSeq 2000
<td>(EBI - ENA) Study: <a href="https://www.ebi.ac.uk/ena/data/view/PRJNA309190" target="_blank">PRJNA309190</a>

<tr><td>17 fastq from Australia bpa-wheat-cultivars<br>Whole‐genome sequence
<td><a href="https://data.bioplatforms.com/organization/bpa-wheat-cultivars" target="_blank">BioPlatforms Austrailia</a>
<td><a href="http://www.wheatgenome.info/" target="_blank">Wheat Genome Info</a>
<br><a href="https://onlinelibrary.wiley.com/doi/abs/10.1111/tpj.13515" target="_blank">The pangenome of hexaploid bread wheat</a>

<tr><td>199 Watkins bread wheat landrace collection<br>
gene based sequence capture (12Mb) to focus on the functionally relevant portion of the genome,<br>followed by paired end sequencing on the Hiseq4000
<td>(EBI - ENA) Study: <a href="https://www.ebi.ac.uk/ena/data/view/PRJEB23320" target="_blank">PRJEB23320</a>
<td><a href="https://link.springer.com/article/10.1007/s00122-014-2344-5" target="_blank">Watkins landrace cultivar collection</a><br>
<a href="https://opendata.earlham.ac.uk/wheat/under_license/toronto/Gardiner_2018-01-29_Watkins-diversity-12Mb">Grassroots Data Repository</a>

<tr><td>500 European wheat lines, exome sequence
<td><td><a href="http://www.whealbi.eu/" target="_blank">Whealbi</a>

<tr><td>38 US WheatCAP Breeding lines
<td><td><a href="http://wheatgenomics.plantpath.ksu.edu/wpdb/" target="_new">Akhunov Lab</a>

<tr><td>10+ Genome Project
<td><td><a href="https://peerj.com/preprints/26877/" target="_new">Roadmap for gene functional characterization in wheat</a>

<tr><td>48 elite bread wheat lines
<td><td><a href="http://plantsciences.montana.edu/" target="_new">Montana State University</a>

<tr><td>1000 wheat exomes project
<td><a href="http://wheatgenomics.plantpath.ksu.edu/1000EC/files/PassportData_160809.xlsx">Meta data</a>,
    <a href="https://www.ebi.ac.uk/ena/data/view/PRJEB31218">VCF data</a>,
    <a href="https://www.ncbi.nlm.nih.gov/sra">NCBI SRA</a>
<td><a href="http://wheatgenomics.plantpath.ksu.edu/1000EC/">Kansas Statue University</a>
</table>
<?php
$footer_div=1;
require $config['root_dir'].'theme/footer.php';

