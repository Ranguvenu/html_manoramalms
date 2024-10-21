<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package BizLMS
 * @subpackage local_courses
 */
$string['pluginname']='Cursos';
$string['organization']='Organización';
$string['mooc']='MOOC';
$string['classroom']='Aula';
$string['elearning']='Aprendizaje electrónico';
$string['learningplan']='Ruta de aprendizaje';
$string['type']='Tipo';
$string['category']='Categoría';
$string['enrolled']='Inscripciones';
$string['completed']='Terminaciones';
$string['manual_enrolment']='Inscripción manual';
$string['add_users']='&lt;&lt;Agregar usuarios';
$string['remove_users']='Eliminar usuarios';
$string['employeesearch']='Filtrar';
$string['agentsearch']='Búsqueda de agentes';
$string['empnumber']='ID de empleado';
$string['email']='Correo electrónico';
$string['band']='Banda';
$string['departments']='Departamentos';
$string['sub_departments']='Subdepartamentos';
$string['sub-sub-departments']='Sub Subdepartamentos';
$string['designation']='Designacion';
$string['im:already_in']='El usuario "{$a}"ya estaba inscrito en este curso';
$string['im:enrolled_ok']='El usuario "{$a}"se ha inscrito con éxito en este curso ';
$string['im:error_addg']='Error al agregar grupo {$a->groupe}  cursar {$a->courseid} ';
$string['im:error_g_unknown']='Error, grupo desconocido {$a} ';
$string['im:error_add_grp']='Error al agregar agrupación {$a->groupe} cursar {$a->courseid}';
$string['im:error_add_g_grp']='Error al agregar grupo {$a->groupe} a agrupar {$a->groupe}';
$string['im:and_added_g']=' y agregado al grupo de Moodle  {$a}';
$string['im:error_adding_u_g']='Error al agregar al grupo  {$a}';
$string['im:already_in_g']=' ya en grupo {$a}';
$string['im:stats_i']='{$a} inscrito &nbsp;&nbsp;';
$string['im:stats_g']='{$a->nb} grupo (s) creado (s): {$a->what} &nbsp;&nbsp;';
$string['im:stats_grp']='{$a->nb} agrupaciones creadas: {$a->what} &nbsp;&nbsp;';
$string['im:err_opening_file']='error al abrir el archivo {$a}';
$string['im:user_notcostcenter']='{$a->user} no asignado a {$a->csname} centro de costos';
$string['mass_enroll']='Inscripciones masivas';
$string['mass_enroll_info'] =
"<p>
Con esta opción vas a inscribir una lista de usuarios conocidos de un archivo con una cuenta por línea
</p>
<p>
<b> La primera línea </b> se omitirán las líneas vacías o las cuentas desconocidas. </p>
<p>
<b>El primero debe contener un correo electrónico único del usuario objetivo.</b>
</p>";
$string['firstcolumn']='La primera columna contiene';
$string['creategroups']='Crear grupo (s) si es necesario';
$string['creategroupings']='Cree agrupaciones si es necesario';
$string['enroll']='Inscríbalos en mi curso';
$string['im:user_unknown']='El usuario con un correo electrónico "{$a}"no existe en el sistema';
$string['points']='Puntos';
$string['createnewcourse']='<i class="icon popupstringicon fa fa-desktop" aria-hidden="true"></i>Crear curso <div class="popupstring">Aquí puedes crear un curso';
$string['editcourse']='<i class="icon popupstringicon fa fa-desktop" aria-hidden="true"></i>Actualizar curso <div class="popupstring">Aquí puedes actualizar el curso';
$string['description']  ='Usuario con nombre de usuario "{$a->userid}"creó el curso"{$a->courseid}"';
$string['desc']  ='Usuario con nombre de usuario "{$a->userid}"ha actualizado el curso"{$a->courseid}"';
$string['descptn']  ='Usuario con nombre de usuario "{$a->userid}"ha eliminado el curso con courseid"{$a->courseid}"';
$string['usr_description']  ='Usuario con nombre de usuario "{$a->userid}"ha creado el usuario con nombre de usuario"{$a->user}"';
$string['usr_desc']  ='Usuario con nombre de usuario "{$a->userid}"ha actualizado al usuario con nombre de usuario"{$a->user}"';
$string['usr_descptn']  ='Usuario con nombre de usuario "{$a->userid}"ha eliminado al usuario con ID de usuario"{$a->user}"';
$string['ilt_description']  ='Usuario con nombre de usuario "{$a->userid}"creó el ilt"{$a->f2fid}"';
$string['ilt_desc']  ='Usuario con nombre de usuario "{$a->userid}"ha actualizado el ilt"{$a->f2fid}"';
$string['ilt_descptn']  ='Usuario con nombre de usuario "{$a->userid}"ha eliminado el ilt"{$a->f2fid}"';
$string['coursecompday']='Días de finalización del curso';
$string['coursecreator']='Creador de cursos';
$string['coursecode']='Código del curso';
$string['addcategory']='<i class="fa fa-desktop popupstringicon" aria-hidden="true"></i><i class="fa fa-desktop secbook popupstringicon cat_pop_icon" aria-hidden="true"></i> Crear nueva categoría <div class="popupstring"></div>';
$string['editcategory']='<i class="fa fa-desktop popupstringicon" aria-hidden="true"></i><i class="fa fa-desktop secbook popupstringicon cat_pop_icon" aria-hidden="true"></i> Actualizar categoría <div class="popupstring"></div>';
$string['coursecat']='Categorías de cursos';
$string['deletecategory']='Eliminar categoría';
$string['top']='Parte superior';
$string['parent']='Padre';
$string['actions']='Comportamiento';
$string['count']='Numero de cursos';
$string['categorypopup']='Categoría {$a}';
$string['missingtype']='Tipo faltante';
$string['catalog']='Catalogar';
$string['nocoursedesc']='No se proporcionó descripción';
$string['apply']='Aplicar';
$string['open_path']='Centro de costos';
$string['uploadcoursespreview']='Cargar vista previa de cursos';
$string['uploadcoursesresult']='Cargar resultados de cursos';
$string['uploadcourses']='Subir cursos';
$string['coursefile']='Archivo';
$string['csvdelimiter']='Delimitador CSV';
$string['encoding']='Codificación';
$string['rowpreviewnum']='Vista previa de filas';
$string['preview']='Avance';
$string['courseprocess']='Proceso del curso';
$string['shortnametemplate']='Plantilla para generar un nombre corto';
$string['templatefile']='Restaurar desde este archivo después de la carga';
$string['reset']='Restablecer el curso después de la carga';
$string['defaultvalues']='Valores de curso predeterminados';
$string['enrol']='Inscribirse';
$string['courseexistsanduploadnotallowedwithargs']='El curso ya existe con el nombre corto "{$a}", elija otro nombre abreviado único.';
$string['canonlycreatecourseincategoryofsameorganisation']='Solo puede crear el curso bajo su organización asignada';
$string['canonlycreatecourseincategoryofsameorganisationwithargs']='No se puede crear un curso en la categoría "{$a}"';
$string['createcategory']='Crear nueva categoría';
$string['manage_course']='Administrar curso';
$string['manage_courses']='Administrar cursos';
$string['leftmenu_browsecategories']='Administrar categorías';
$string['courseother_details']='Otros detalles';
$string['view_courses']='ver cursos';
$string['deleteconfirm']='Estas seguro que quieres borrarlo "<b>{$a->name}</b>" ¿curso?<br> Una vez eliminado, no se puede revertir.';
$string['department']='Departamento';
$string['coursecategory']='Categoría';
$string['fullnamecourse']='Nombre completo';
$string['coursesummary']='Resumen';
$string['courseoverviewfiles']='Imagen de banner';
$string['startdate']='Fecha de inicio';
$string['enddate']='Fecha final';
$string['program']='Programa';
$string['certification']='Certificación';
$string['create_newcourse']='Crear nuevo curso';
$string['userenrolments']='Inscripciones de usuarios';
$string['certificate']='Certificado';
$string['points_positive']='Los puntos deben ser mayores que 0';
$string['coursecompletiondays_positive']='Los días de finalización deben ser superiores a 0';
$string['enrolusers']='Inscribir usuarios';
$string['grader']='Calificador';
$string['activity']='Actividad';
$string['courses']='Cursos';
$string['nocategories']='No hay categorías disponibles';
$string['nosameenddate']='La "fecha de finalización" no debe ser menor que la "fecha de inicio"';
$string['coursemanual']='Descargue una hoja de Excel de muestra y complete los valores de campo en el formato especificado a continuación.';
$string['help_1']='<table border="1">
<tbody><tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Campos obligatorios</b></td></tr>
<tr>
<th>Campo</th><th>Restricción</th>
</tr>
<tr><td>nombre completo</td><td>Nombre completo del curso.</td></tr>
<tr><td>código del curso</td><td>curso-código del curso.</td></tr>
<tr><td>category_code</td><td>Ingrese el código de categoría (puede encontrar este código en la página administrar categorías).</td></tr>
<tr><td>tipo de curso</td><td>Tipo de curso (separado por comas) (Ej: aula, elearning, certificación, ruta de aprendizaje, programa).</td></tr>
<tr><td>días de finalización</td><td>Tipo de curso (separado por comas) (Ej: aula, elearning, certificación, ruta de aprendizaje, programa).</td></tr>
<tr>';
$string['help_2']='<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Campos normales</b></td></tr>
<tr>
<th>Campo</th><th>Restricción</th>
</tr>
<tr><td>Resumen</td><td>Resumen del curso.</td></tr>
<tr><td>Costo</td><td>Costo del curso.</td></tr>
<tr><td>Cliente</td><td>Nombre corto del cliente.</td></tr>
<tr><td>LOB</td><td>Nombre corto del LOB.</td></tr>
<tr><td>Puntos</td><td>Puntos para el curso.</td></tr>
</tbody></table>';
$string['back_upload']='Volver a subir cursos';
$string['manual']='Manual de ayuda';
$string['enrolledusers']='Usuarios inscritos';
$string['notenrolledusers']='Usuarios no inscritos';
$string['finishbutton']='Terminar';
$string['updatecourse']='Actualizar curso';
$string['course_name']='Nombre del curso';
$string['completed_users']='Usuarios completados';
$string['course_filters']='Filtros de cursos';
$string['back']='atrás';
$string['sample']='Muestra';
$string['selectdept']='--Seleccione Departamento--';
$string['selectsubdept']='--Seleccione Subdepartamento--';
$string['selectorg']='--Seleccione Organización--';
$string['selectcat']='--Selecciona una categoría--';
$string['select_cat']='--Seleccione Categorías--';
$string['reset']='Reiniciar';
$string['err_category']='Por favor seleccione Categoría';
$string['availablelist']='<b>Usuarios disponibles ({$a})</b>';
$string['selectedlist']='Usuarios seleccionados';
$string['status']='Estado';
$string['select_all']='Seleccionar todo';
$string['remove_all']='Un Seleccionar ';
$string['not_enrolled_users']='<b>Usuarios no inscritos ({$a})</b>';
$string['enrolled_users']='<b> Usuarios registrados ({$a})</b>';
$string['remove_selected_users']='<b> Anular la inscripción de usuarios </b><i class="fa fa-arrow-right" aria-hidden="true"></i><i class="fa fa-arrow-right" aria-hidden="true"></i>';
$string['remove_all_users']='<b> Anular la inscripción de todos los usuarios </b><i class="fa fa-arrow-right" aria-hidden="true"></i><i class="fa fa-arrow-right" aria-hidden="true"></i>';
$string['add_selected_users']='<i class="fa fa-arrow-left" aria-hidden="true"></i><i class="fa fa-arrow-left" aria-hidden="true"></i><b> Inscribir usuarios</b>';
$string['add_all_users']=' </div><i class="fa fa-arrow-left" aria-hidden="true"></i><i class="fa fa-arrow-left" aria-hidden="true"></i> </div><b> Inscribir a todos los usuarios </b>';
$string['course_status_popup']='Estado de actividad para {$a}';
$string['auto_enrol']='Inscripción automática';
$string['need_manage_approval']='Necesita la aprobación del administrador';
$string['costcannotbenonnumericwithargs']='El costo debe ser numérico pero dado "{$a}"';
$string['pointscannotbenonnumericwithargs']='Los puntos deben ser numéricos pero indicados "{$a}"';
$string['need_self_enrol']='Necesita autoinscripción';
$string['enrolluserssuccess']='<b>{$a->changecount}</b> Empleados inscritos con éxito en este <b>"{$a->course}"</b> curso .';
$string['unenrolluserssuccess']='<b>{$a->changecount}</b> Empleado (s) se anuló con éxito de este <b>"{$a->course}"</b> curso .';
$string['enrollusers']='Curso <b>"{$a}"</b> la inscripción está en proceso ...';
$string['un_enrollusers']='Curso <b>"{$a}"</b> Un registro está en proceso ...';
$string['click_continue']='Haga clic en continuar';
$string['bootcamp']='XSeeD';
$string['manage_br_courses']='Administrar <br> cursos';
$string['nocourseavailiable']='No hay cursos disponibles';
$string['taskcoursenotification']='Tarea de notificación del curso';
$string['taskcoursereminder']='Tarea de recordatorio del curso';
$string['pleaseselectorganization']='Seleccione Organización';
$string['pleaseselectcategory']='Seleccione una categoría';
$string['enablecourse']='¿Estás seguro de activar el curso? <b>{$a}</b>';
$string['disablecourse']='¿Estás seguro de desactivar el curso? <b>{$a}</b>';
$string['courseconfirm']='Confirmar';
$string['open_pathcourse_help']='Organización del curso';
$string['open_departmentidcourse_help']='Departamento del curso';
$string['open_identifiedascourse_help']='Tipo de curso (selección múltiple)';
$string['open_pointscourse_help']='Puntos por defecto del curso (0)';
$string['selfenrolcourse_help']='Marque sí si requiere autoinscripción al curso';
$string['approvalrequiredcourse_help']='Marque sí si es necesario para habilitar el administrador de solicitudes para inscribirse en el curso';
$string['open_costcourse_help']='Costo del curso';
$string['open_skillcourse_help']='Habilidad lograda al finalizar el curso';
$string['open_levelcourse_help']='Nivel alcanzado al finalizar el curso';
$string['open_pathcourse']='Organización';
$string['open_departmentidcourse']='Departamento';
$string['open_identifiedascourse']='Tipo';
$string['open_pointscourse']='Puntos';
$string['selfenrolcourse']='autoinscripción';
$string['approvalrequiredcourse']='administrador de solicitudes para la inscripción';
$string['open_costcourse']='Costo';
$string['open_skillcourse']='Habilidad ';
$string['open_levelcourse']='Nivel';
$string['notyourorg_msg']='Ha intentado ver esta actividad no pertenece a su organización';
$string['notyourdept_msg']='Ha intentado ver esta actividad no pertenece a su Departamento';
$string['notyourorgcourse_msg']='Ha intentado ver este curso no pertenece a su organización';
$string['notyourdeptcourse_msg']='Ha intentado ver este curso no pertenece a su departamento';
$string['notyourorgcoursereport_msg']='Ha intentado ver este reporte de calificador no es el curso de su organización, por lo que no puede acceder a esta página';
$string['need_manager_approval ']='need_manager_approval';
$string['categorycode']='Código de categoría';
$string['categorycode_help']='El código de categoría de una categoría de curso solo se utiliza cuando se compara la categoría con sistemas externos y no se muestra en ninguna parte del sitio. Si la categoría tiene un nombre de código oficial, se puede ingresar; de lo contrario, el campo se puede dejar en blanco.';
$string['categories']='Subcategorías:  ';
$string['makeactive']='Activar';
$string['makeinactive']='Hacer inactivo';
$string['courses:bulkupload']='Carga masiva';
$string['courses:create']='Crear curso';
$string['courses:delete']='Eliminar curso';
$string['courses:grade_view']='Vista de calificación';
$string['courses:manage']='Administrar cursos';
$string['courses:report_view']='Vista de reporte';
$string['courses:unenrol']='Darse de baja del curso';
$string['courses:update']='Curso de actualización';
$string['courses:view']='Ver curso';
$string['courses:visibility']='Visibilidad del curso';
$string['courses:enrol']='Inscripción al curso';
$string['reason_linkedtocostcenter']='Como esta categoría de curso está vinculada con la organización / departamento, no puede eliminar esta categoría';
$string['reason_subcategoriesexists']='Como tenemos subcategorías en esta categoría de curso, no puede eliminar esta categoría.';
$string['reason_coursesexists']='Como tenemos cursos en esta categoría de cursos, no puede eliminar esta categoría.';
$string['reason']='Razón';
$string['completiondayscannotbeletter']='No se puede crear un curso con días de finalización como {$a} ';
$string['completiondayscannotbeempty']='No se puede crear un curso sin días de finalización.';
$string['tagarea_courses']='Cursos';
$string['subcategories']='Subcategorías';
$string['tag']='Etiqueta';
$string['tag_help']='etiqueta';
$string['open_subdepartmentcourse_help']='Subdepartamento del curso';
$string['open_subdepartmentcourse']='Sub-Departamento';
$string['suspendconfirm']='Confirmación';
$string['activeconfirm']='¿Está seguro de activar la categoría?';
$string['inactiveconfirm']='¿Está seguro de desactivar la categoría?';
$string['yes']='Confirmar';
$string['no']='Cancelar';
$string['add_certificate']='Agregar certificado';
$string['add_certificate_help']='Si desea emitir un certificado cuando el usuario complete este curso, habilite aquí y seleccione la plantilla en el siguiente campo (Plantilla de certificado)';
$string['select_certificate']='Seleccionar certificado';
$string['certificate_template']='Plantilla de certificado';
$string['certificate_template_help']='Seleccione la plantilla de certificado para este curso';
$string['err_certificate']='Falta la plantilla de certificado';
$string['download_certificate']='Descargar certificado';
$string['unableto_download_msg'] = "Still this user didn't completed the course, so you cann't download the certificate";
$string['completionstatus']='Estado de finalización';
$string['completiondate']='Fecha de Terminación';
$string['nousersmsg']='No hay usuarios disponibles';
$string['employeename']='Nombre de empleado';
$string['completed']='Completado';
$string['notcompleted']='no completado';
$string['messageprovider:course_complete']='Curso completo';
$string['messageprovider:course_enrol']='Darse de baja del curso';
$string['messageprovider:course_notification']='Notificación del curso';
$string['messageprovider:course_reminder']='Recordatorio del curso';
$string['messageprovider:course_unenroll']='Darse de baja del curso';
$string['completed_courses']='Cursos completados';
$string['inprogress_courses']='Cursos en curso';
$string['selectcourse']='Seleccionar curso';
$string['enrolmethod']='Método de inscripción';
$string['deleteuser']='Eliminar confirmación';
$string['confirmdelete']='¿Está seguro, desea cancelar la inscripción de este usuario?';
$string['edit']='Editar';
$string['err_points']='Los puntos no pueden estar vacíos';
$string['browseevidences']='Examinar evidencia';
$string['courseevidencefiles']='Evidencia del curso';
$string['courseevidencefiles_help']='La evidencia del curso se muestra en la descripción general del curso en el Panel de control. El administrador del sitio puede habilitar tipos de archivos adicionales aceptados y más de un archivo. Si es así, estos archivos se mostrarán junto al resumen del curso en la página de lista de cursos.';
$string['browseevidencesname']='{$a} Evidencias';
$string['selfcompletion']='Auto finalización';
$string['selfcompletionname']='{$a} Auto finalización';
$string['selfcompletionconfirm']='¿Estás seguro, quieres cursar "{$a}"autocompletación.';
$string['saveandcontinue']='Guardar Continuar';
$string['courseoverview']='Resumen del curso';
$string['selectlevel']='Selecciona el nivel';
$string['errorinrequestprocessing']='Se produjo un error al procesar las solicitudes';
$string['featuredcourses']='Cursos destacados';
$string['errorinsubmission']='Error en la presentación';
$string['recentlyenrolledcourses']='Cursos inscritos recientemente';
$string['recentlyaccessedcourses']='Cursos de acceso reciente';
$string['securedcourse']='Curso seguro';
$string['open_securecourse_course']='Curso seguro';
$string['open_securecourse_course_help']='Una vez seleccionado como sí, este curso no se mostrará en la aplicación móvil.';
$string['parent_category'] = 'Categoría principal';
$string['parent_category_code'] = 'Código de categoría principal';
$string['select_skill'] = 'Seleccionar habilidad';
$string['select_level'] = 'Selecciona el nivel';
$string['what_next'] = "Que sigue?";
$string['doyouwantto_addthecontent'] = 'Quieres <b>agregar el contenido</b>';
$string['doyouwantto_enrolusers'] = 'Quieres <b>inscribir usuarios</b>';
$string['goto'] = 'Ir';
$string['search'] = 'Buscar';
$string['no_users_enrolled'] = 'No hay usuarios inscritas en este curso';
$string['missingfullname'] = 'Ingrese un nombre de curso válido';
$string['missingshortname'] = 'Ingrese un código de curso válido';
$string['missingtype'] = 'Seleccione el tipo';
$string['course_reports'] = 'Informes del curso';
$string['cannotuploadcoursewithlob'] = 'Sin el cliente no se puede cargar un curso con LOB';
$string['categorycodeshouldbedepcode'] = 'El código de categoría debe ser el nombre corto del cliente i.e \'{$a}\'';
$string['categorycodeshouldbesubdepcode'] = 'El código de categoría debe ser el nombre corto de LOB i.e \'{$a}\'';
$string['course_name_help'] = 'Nombre del curso';
$string['coursecode_help'] = 'Código del curso';
