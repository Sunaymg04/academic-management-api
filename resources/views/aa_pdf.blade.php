<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@php
    $editableResolucion = $editableResolucion ?? false;
    $previewHtml = $previewHtml ?? false;
    $fieldAttrs = function ($field) use ($editableResolucion) {
        return $editableResolucion
            ? 'data-field="'.$field.'" contenteditable="true" spellcheck="false" class="editable-field"'
            : '';
    };
    $logoIzq = $logoIzq ?? ($previewHtml ? asset('images/logo_izq.png') : public_path('images/logo_izq.png'));
    $logoDer = $logoDer ?? ($previewHtml ? asset('images/logo_der.png') : public_path('images/logo_der.png'));
@endphp

<style>
body {
    font-family:  Arial, sans-serif;
    font-size: 12pt;
    line-height: 1.4;
}
/* ENCABEZADO */
.header {
    text-align: center;
    font-weight: bold;
}

/* TITULO */
.titulo {
    margin-top: 15px;
    margin-bottom: 10px;
}

/* PARRAFOS JUSTIFICADOS */
.parrafo {
    text-align: justify;
    margin-bottom: 10px;
}

/* PARRAFO CON SANGRIA */
.parrafo-sangria {
    text-align: justify;
    text-indent: 30px;
    margin-bottom: 10px;
}

/* RESUELVO */
.resuelvo {
    text-align: center;
    margin: 20px 0;
    letter-spacing: 5px;
    font-weight: bold;
}

/* ARTICULOS */
.articulo {
    text-align: justify;
    margin-top: 10px;
}

/* TABLA */
.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    table-layout: fixed;
}
.header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
}

.header-table td {
    border: none;
    vertical-align: middle;
}

/* LOGOS */
.logo {
    width: 20%;
    text-align: center;
}

.logo img {
    width: 60px;
}

/* TEXTO */
.header-text {
    width: 60%;
    text-align: center;
    font-size: 12pt;
    font-family: Arial, sans-serif;
    white-space: nowrap;
}
.titulo-bloque {
    margin-top: 10px;
    margin-bottom: 10px;
}

.decano {
    font-weight: bold;
    margin-bottom: 2px; /* 🔥 controla separación */
}

.resolucion {
    font-weight: bold;
}
.linea-header {
    border: none;
    border-top: 2px solid black; /* 🔥 grosor y color */
    margin-top: 8px;
    margin-bottom: 12px;
}

.table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed; /* 🔥 clave */
}

.table th, .table td {
    border: 1px solid black;
    padding: 4px;
    font-size: 9.5pt;
    font-family: Arial, sans-serif;
    vertical-align: middle;
    word-wrap: break-word;
}

.table th {
    font-weight: bold;
    text-align: center;
}

.center {
    text-align: center;
}

/* FIRMA */
.firma {
    margin-top: 40px;
}

/* 🔥 tamaños personalizados */
.col-no { width: 6%; }
.col-carnet { width: 18%; }
.col-nombre { width: 30%; }
.col-ano { width: 9%; }
.col-tutor { width: 27%; }
.col-etapa { width: 10%; }
.departamento-docente {
    font-weight: bold;
    margin: 10px 0 4px 0;
}
@if($editableResolucion)
.editable-field {
    cursor: text;
    border-radius: 2px;
}

.editable-field:hover,
.editable-field:focus {
    outline: 1px dashed #2563eb;
    background: #eff6ff;
}

.editable-logo {
    cursor: pointer;
    border-radius: 4px;
}

.editable-logo:hover {
    outline: 2px dashed #2563eb;
    outline-offset: 4px;
}
@endif

</style>

</head>

<body>

<!-- 🟣 ENCABEZADO -->
<!-- 🟣 ENCABEZADO NUEVO -->
<table class="header-table">
    <tr>
        <td class="logo">
            <img src="{{ $logoIzq }}" @if($editableResolucion) data-field="logo_izq" data-logo-field="logo_izq" class="editable-logo" @endif>
        </td>

        <td class="header-text">
            <div>UNIVERSIDAD CENTRAL “MARTA ABREU” DE LAS VILLAS</div>
            <div><strong>{{ $nombreFacultadMayus }}</strong></div>

        </td>

        <td class="logo">
            <img src="{{ $logoDer }}" @if($editableResolucion) data-field="logo_der" data-logo-field="logo_der" class="editable-logo" @endif>
        </td>
    </tr>
</table>

<hr class="linea-header">

<!-- 🟣 TITULO -->
 <div class="titulo-bloque">
    <div class="decano">EL DECANO</div>
    <div class="resolucion">Resolución Decanal /<span {!! $fieldAttrs('anio_resolucion') !!}>{{ $camposEditables['anioResolucion'] ?? $anio }}</span></div>
</div>
<!-- 🟣 POR CUANTO -->
<p class="parrafo">
<strong>POR CUANTO:</strong> La Resolución Ministerial <span {!! $fieldAttrs('resolucion_ministerial') !!}>{{ $camposEditables['resolucionMinisterial'] ?? '47/2022' }}</span> dictada por el Ministro de Educación Superior con fecha <span {!! $fieldAttrs('fecha_resolucion_ministerial') !!}>{{ $camposEditables['fechaResolucionMinisterial'] ?? '27 de mayo de 2022' }}</span> establece en su Capítulo <span {!! $fieldAttrs('capitulo') !!}>{{ $camposEditables['capitulo'] ?? 'IX' }}</span> las normas y procedimientos para el trabajo metodológico.
</p>

<p class="parrafo">
<strong>POR CUANTO:</strong> La Resolución Ministerial <span {!! $fieldAttrs('resolucion_ministerial') !!}>{{ $camposEditables['resolucionMinisterial'] ?? '47/2022' }}</span> en <strong>su capítulo <span {!! $fieldAttrs('capitulo') !!}>{{ $camposEditables['capitulo'] ?? 'IX' }}</span>, artículo <span {!! $fieldAttrs('articulo_colectivo') !!}>{{ $camposEditables['articuloColectivo'] ?? '153' }}</span> </strong>establece que el colectivo de año es el encargado de llevar a cabo el trabajo metodológico en este nivel organizativo. Constituye un nivel de dirección atípico en la estructura de las instituciones de educación superior, conducido por el profesor principal del año académico. Agrupa a los profesores que desarrollan las asignaturas del año, a los profesores guías de cada grupo, a los tutores y a los representantes de las organizaciones estudiantiles.
</p>

<p class="parrafo-sangria">
Este colectivo tiene como propósito lograr el cumplimiento con calidad de los objetivos de formación del año académico, así como otros que se hayan concertado para responder a las características propias del grupo y del momento, mediante la implementación de la estrategia educativa del año académico.</p>

<p class="parrafo">
<strong>POR CUANTO:</strong> La Resolución Ministerial <span {!! $fieldAttrs('resolucion_ministerial') !!}>{{ $camposEditables['resolucionMinisterial'] ?? '47/2022' }}</span> en <strong>su capítulo <span {!! $fieldAttrs('capitulo') !!}>{{ $camposEditables['capitulo'] ?? 'IX' }}</span>, artículo <span {!! $fieldAttrs('articulo_conduccion') !!}>{{ $camposEditables['articuloConduccion'] ?? '156' }}</span></strong> establece que la conducción del colectivo de año debe estar a cargo de un profesor que posea una buena preparación pedagógica y científica, así como cualidades y actitudes que le permitan desempeñarse en esa responsabilidad. Es la principal autoridad académica del año y se subordina directamente al decano de la facultad-carrera o al jefe del departamento-carrera, según corresponda. </p>

<p class="parrafo-sangria">
Su trabajo es esencial para el cumplimiento de los objetivos de formación del año. Su labor de dirección metodológica está basada en la coordinación, la asesoría y el control de los profesores guías, los tutores y el colectivo de profesores del año.</p>

<!-- 🟣 POR TANTO -->
<p class="parrafo">
<strong>POR TANTO:</strong> En uso de las facultades que, me están conferidas
</p>

<!-- 🟣 RESUELVO -->
<div class="resuelvo">R E S U E L V O</div>

<!-- 🟣 PRIMERO -->
<p class="articulo">
<strong>PRIMERO:</strong> Designar a los Alumnos Ayudantes para el primer período del curso <span {!! $fieldAttrs('curso_resolucion') !!}>{{ $camposEditables['cursoResolucion'] ?? $ano }}</span>
</p>

@foreach($designados as $grupo)
<p class="departamento-docente">Departamento Docente: {{ $grupo['departamento'] }}</p>

<!-- 🟣 TABLA -->
<table class="table">
<thead>
<tr>
<th class="col-no">N°</th>
    <th class="col-carnet">C. DE IDENTIDAD</th>
    <th class="col-nombre">NOMBRES Y APELLIDOS</th>
    <th class="col-ano">AÑO</th>
    <th class="col-tutor">TUTOR</th>
    <th class="col-etapa">ETAPA</th>
</tr>
</thead>

<tbody>

        @foreach($grupo['items'] as $fila)
       <tr>
    <td>{{ $fila['no'] }}</td>
    <td>{{ $fila['carnet'] }}</td>
    <td>{{ $fila['nombre'] }}</td>
    <td>{{ $fila['anio'] }}</td>
    <td>{{ $fila['tutor'] }}</td>
    <td>{{ $fila['etapa'] }}</td>
</tr>
        @endforeach
    </tbody>

</table>
@endforeach


<!-- 🟣 Segunda -->
<p class="articulo">
<strong>SEGUNDO:</strong>  Desnombrar a los siguientes Alumnos Ayudantes para el primer período del curso <span {!! $fieldAttrs('curso_resolucion') !!}>{{ $camposEditables['cursoResolucion'] ?? $ano }}</span>
</p>

@foreach($desnombrados as $grupo)
<p class="departamento-docente">Departamento Docente: {{ $grupo['departamento'] }}</p>

<!-- 🟣 TABLA -->
<table class="table">
<thead>
<tr>
<th class="col-no">N°</th>
    <th class="col-carnet">C. DE IDENTIDAD</th>
    <th class="col-nombre">NOMBRES Y APELLIDOS</th>
    <th class="col-ano">AÑO</th>
    <th class="col-tutor">TUTOR</th>
    <th class="col-etapa">ETAPA</th>
</tr>
</thead>

<tbody>



@foreach($grupo['items'] as $fila)
<tr>
    <td>{{ $fila['no'] }}</td>
    <td>{{ $fila['carnet'] }}</td>
    <td>{{ $fila['nombre'] }}</td>
    <td>{{ $fila['anio'] }}</td>
    <td>{{ $fila['tutor'] }}</td>
    <td>{{ $fila['etapa'] }}</td>
</tr>
@endforeach
</tbody>
</table>
@endforeach

<!-- 🟣 Tercero -->
<p class="articulo">
<strong>TERCERO:</strong> Los profesores designados como tutores de los Alumnos Ayudantes elaborarán el
Plan de Trabajo del AA, dirigido a lograr su formación como docente o al trabajo científico
técnico, de acuerdo a las necesidades institucionales y a las motivaciones que posea el
estudiante. El mismo será aprobado por el Jefe de Dpto. docente correspondiente antes de
concluir el mes de mayo del año en curso y se archivará en una carpeta habilitada en el
Departamento docente para los Alumnos Ayudantes.
</p>

<p class="articulo">
<strong>CUARTO:</strong>
 Los Jefes de Departamento son responsables de revisar la Evaluación del Alumno
Ayudante que será realizada por el Tutor a partir del cumplimiento de su Plan de Trabajo, sus
resultados docentes y la disciplina observada. Se realizará una evaluación parcial al concluir el
primer periodo que se archivará en la Carpeta de Alumnos Ayudantes del Departamento
Docente y una evaluación al finalizar el curso que se archivará en el expediente del estudiante
ubicado en la Secretaría Docente de la Facultad, con copia en el expediente del Alumno
Ayudante en el Departamento Docente.
</p>

<!-- 🟣 FINAL -->
<p class="parrafo-sangria">
Esta Resolución entra en vigor a partir de su firma.
</p>

<p class="parrafo">
<strong>NOTIFÍQUESE</strong> la presente resolución a los profesores designados, al Vice Decano Docente, Vicedecano de Investigación y Postgrado, Jefes de Departamentos Docentes y a cuantas personas más deban conocer el contenido de la presente.
</p>

<p class="parrafo">
<strong>ARCHÍVESE</strong> el original en el protocolo de Disposiciones de {{ $nombreFacultad }}
</p>

<p class="parrafo">
<strong>DADA</strong> en la Universidad Central “Marta Abreu” de Las Villas, a los <span {!! $fieldAttrs('dia_archivese') !!}>{{ $camposEditables['diaArchivese'] ?? $dia }}</span> días del mes de <span {!! $fieldAttrs('mes_archivese') !!}>{{ $camposEditables['mesArchivese'] ?? $mes }}</span> de <span {!! $fieldAttrs('anio_archivese') !!}>{{ $camposEditables['anioArchivese'] ?? $ano }}</span>. “<span {!! $fieldAttrs('revolucion_texto') !!}>{{ $camposEditables['revolucionTexto'] ?? ('AÑO '.$revolucion.' DE LA REVOLUCION') }}</span>”.
</p>

<!-- 🟣 FIRMA -->
<div class="firma">
    <p>__________________________</p>
    <p>Dr. C. {{ $nombreDecano }}</p>
</div>

@if($editableResolucion)
<script>
document.addEventListener('input', function (event) {
    var field = event.target && event.target.dataset ? event.target.dataset.field : null;

    if (!field) {
        return;
    }

    document.querySelectorAll('[data-field="' + field + '"]').forEach(function (element) {
        if (element !== event.target) {
            element.textContent = event.target.textContent;
        }
    });
});
</script>
@endif

</body>
</html>
