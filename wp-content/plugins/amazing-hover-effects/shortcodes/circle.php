<?php


    $effect = $options['circle_effect'];
    $animation = $options['circle_animation'];
    $image_width = $options['circle_image_width'];
    $image_height = $options['circle_image_height'];
    $row_space = $options['circle_row_space'];
    $column_space = $options['circle_column_space'];

    



   
    if ($underline) {
        $underline = 'none';
    }

    if( ! empty( $options['circle_option'] ) ) {

    $groups = $options['circle_option'];
   
    
    $output = '<div class="hover-cols">';

        foreach( $groups as $group ){
            
        $image = $group['circle_image'];
        
        $image = wp_get_attachment_image_src( $image, 'full' );
        
        $output .='<li class="hover_effects_li" style="margin: 0 '.$column_space.'px '.$row_space.'px;">';
        
        if($effect=="effect1"){
            $output .= '<div class="ih-item circle effect1"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <div class="info-back" >
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></div></a></div>';
        }


        if($effect=="effect2"){
            $output .= '<div class="ih-item circle effect2 '.$animation.'"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></a></div>';
        }

        if($effect=="effect3"){
            $output .= '<div class="ih-item circle effect3 '.$animation.'"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></a></div>';
        }
          
        if($effect=="effect4"){
            $output .= '<div class="ih-item circle effect4 '.$animation.'"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info>
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></a></div>';
        }
        
        if($effect=="effect5"){
            $output .= '<div class="ih-item circle effect5 '.$animation.'"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <div class="info-back">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></div></a></div>';
        }        

        if($effect=="effect6"){
            $output .= '<div class="ih-item circle effect6 scale_down_up"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></a></div>';
        }

        if($effect=="effect7"){
            $output .= '<div class="ih-item circle effect7 '.$animation.'"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <div class="info-back">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></div></a></div>';
        }
        
        if($effect=="effect8"){
            $output .= '<div class="ih-item circle effect8 '.$animation.'"><a href="'.$group['circle_link'].'">
                                <div class="img-container">
                                  <div class="img"><img src="'.$image[0].'"></div>
                                </div>
                                <div class="info-container">
                                  <div class="info">
                                    <h3>'.$group['circle_title'].'</h3>
                                    <p>'.$group['circle_desc'].'</p>
                                  </div>
                </div></a></div>';
        }        
        
        if($effect=="effect9"){
            $output .= '<div class="ih-item circle effect9 '.$animation.'"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></a></div>';
        }
        
        if($effect=="effect10"){
            $output .= '<div class="ih-item circle effect10 '.$animation.'"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></a></div>';
        }        

        if($effect=="effect11"){
            $output .= '<div class="ih-item circle effect11 '.$animation.'"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></a></div>';
        }        

        if($effect=="effect12"){
            $output .= '<div class="ih-item circle effect12 '.$animation.'"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></a></div>';
        }
        
        if($effect=="effect13"){
            $output .= '<div class="ih-item circle effect13 from_left_and_right"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <div class="info-back">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></div></a></div>';
        }
        
        if($effect=="effect14"){
            $output .= '<div class="ih-item circle effect14 '.$animation.'"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></a></div>';
        }        
        
        
        if($effect=="effect15"){
            $output .= '<div class="ih-item circle effect15 '.$animation.'"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></a></div>';
        }
        
        if($effect=="effect16"){
            $output .= '<div class="ih-item circle effect16 '.$animation.'"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></a></div>';
        }        
        
        if($effect=="effect17"){
            $output .= '<div class="ih-item circle effect17"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></a></div>';
        }                        
        

        if($effect=="effect18"){
            $output .= '<div class="ih-item circle effect18 '.$animation.'"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <div class="info-back">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></div></a></div>';
        }

        if($effect=="effect19"){
            $output .= '<div class="ih-item circle effect19"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></a></div>';
        }

        if($effect=="effect20"){
            $output .= '<div class="ih-item circle effect20 '.$animation.'"><a href="'.$group['circle_link'].'">
                            <div class="spinner"></div>
                                <div class="img"><img src="'.$image[0].'"></div>
                                    <div class="info">
                                <div class="info-back">
                                <h3>'.$group['circle_title'].'</h3>
                                <p>'.$group['circle_desc'].'</p>
                    
                    </div></div></a></div>';
        }
                  
    $output .='</li>'; 
          
    }
        
    $output .= '</div>';   
         

}   