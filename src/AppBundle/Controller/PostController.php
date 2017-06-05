<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Post;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * Post controller.
 *
 * @Route("/")
 */
class PostController extends Controller
{
    /**
     * Lists all post entities.
     *
     * @Route("/", name="_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $posts = $em->getRepository('AppBundle:Post')->getAllQuery();

        $page = $request->query->get('page', 1);
        $limit = 1;
        $paginator = $this->paginate($posts, $page, $limit);
        $maxPages = ceil($paginator->count() / $limit);
//        $this->disqusBundle();

        return $this->render(
            'post/index.html.twig',
            array(
                'posts' => $posts->getResult(),
                'count' => $paginator->count() / $limit,
                'currentPage' => $page,
                'maxPages' => $maxPages,
            )
        );
    }

    public function paginate(Query $dql, $page = 1, $limit = 1)
    {
        $paginator = new Paginator($dql);

        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))// Offset
            ->setMaxResults($limit); // Limit

        return $paginator;
    }

    function commentsAction()
    {
        $comments = $this->get('knp_disqus.request')->fetch(
            'dolor-sid',
            array(
                'identifier' => '/december-2010/the-best-day-of-my-life/',
                'limit' => 10, // Default limit is set to max. value for Disqus (100 entries)
                //    'language'   => 'de_formal', // You can fetch comments only for specific language
            )
        );

        return $this->render(
            'post/comment.html.twig',
            array(
                'comments' => $comments,
            )
        );
    }

    /**
     * Creates a new post entity.
     *
     * @Route("/new", name="_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $post = new Post();
        $form = $this->createForm('AppBundle\Form\PostType', $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($post);
            $em->flush();

            return $this->redirectToRoute('_show', array(
                'id' => $post->getId()
            ));
        }


        return $this->render(
            'post/new.html.twig',
            array(
                'post' => $post,
                'form' => $form->createView(),
            )
        );
    }

    /**
     * Finds and displays a post entity.
     *
     * @Route("/post{id}", name="_show")
     * @Method("GET")
     * @param Post $post
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Post $post)
    {
        $deleteForm = $this->createDeleteForm($post);

        return $this->render(
            'post/show.html.twig',
            array(
                'post' => $post,
                'delete_form' => $deleteForm->createView(),
                'comments' => $this->commentsAction(),
            )
        );
    }

    /**
     * Displays a form to edit an existing post entity.
     *
     * @Route("/post{id}/edit", name="_edit")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param Post $post
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, Post $post)
    {
        $deleteForm = $this->createDeleteForm($post);
        $editForm = $this->createForm('AppBundle\Form\PostType', $post);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('_show', array('id' => $post->getId()));
        }

        return $this->render(
            'post/edit.html.twig',
            array(
                'post' => $post,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
            )
        );
    }

    /**
     * Deletes a post entity.
     *
     * @Route("/{id}", name="_delete")
     * @Method("DELETE")
     * @param Request $request
     * @param Post $post
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, Post $post)
    {
        $form = $this->createDeleteForm($post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($post);
            $em->flush();
        }

        return $this->redirectToRoute('_index');
    }

    /**
     * Creates a form to delete a post entity.
     *
     * @param Post $post The post entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Post $post)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('_delete', array('id' => $post->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }
}
