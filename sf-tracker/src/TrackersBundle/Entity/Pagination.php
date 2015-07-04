<?php
namespace TrackersBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;


class Pagination {

	public function render($pageht, $pagept,$function) {

        $temp ="";
        if($pagept!=null){
            if($pageht==0)
                $temp = $temp."";//"<li class='prev'><a href='javascript:void(0);'> &lt; </a></li>";
            else
                $temp = $temp."<li class='prev'><a href='javascript:void(0);' onclick='".$function."(".($pageht-1).");' >&lt;</a><li>";


            if($pagept>10 )
            {
                for($i=0;$i<3;$i++)
                {
                    if($pageht!=$i)
                        $temp = $temp."<li><a href='javascript:void(0);' onclick='".$function."(".$i.");' >".($i+1)."</a></li>";
                    else
                        $temp = $temp."<li ><a class='active' href='javascript:void(0);'>".($i+1)."</a></li>";
                }



                if($pageht>=2 &&  $pageht <= ($pagept-4) )
                {

                    if($pageht>=4)
                    {
                        if($pageht>4)
                            $temp = $temp."<li>...</li>";

                        $temp = $temp."<li><a href='javascript:void(0);' onclick='".$function."(".($pageht-1).");' >".($pageht)."</a></li>";
                    }
                    if($pageht!=($pagept-4) ){
                        if($pageht!=2 )
                            $temp = $temp."<li ><a class='active' href='javascript:void(0);'>".($pageht+1)."</a></li>";

                        $temp = $temp."<li><a href='javascript:void(0);' onclick='".$function."(".($pageht+1).");' >".($pageht+2)."</a></li>";
                    }

                }

                $temp = $temp."...";

                for($i = ($pagept-4);$i<$pagept;$i++)
                {
                    if($pageht!=$i)
                        $temp = $temp."<li><a href='javascript:void(0);' onclick='".$function."(".$i.");' >".($i+1)."</a><li>";
                    else
                        $temp = $temp."<li><a class='active' href='javascript:void(0);'>".($i+1)."</a></li>";
                }
            }
            else
            {
                for($i=0;$i<$pagept;$i++)
                {
                    if($pageht!=$i)
                        $temp = $temp."<li><a href='javascript:void(0);' onclick='".$function."(".$i.");' >".($i+1)."</a></li>";
                    else
                        $temp = $temp."<li ><a class='active' href='javascript:void(0);'>".($i+1)."</a></li>";
                }
            }

            if($pageht==($pagept-1))
                $temp = $temp.''; //<li class="next"><a href="javascript:void(0);">&gt;</a></li>';
            else
                $temp = $temp."<li class='next'><a href='javascript:void(0);' onclick='".$function."(".($pageht+1).");' > &gt; </a></li>";



        }
        return $temp;
    }
}
?>