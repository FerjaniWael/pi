<?php

namespace App\Controller;
use Dompdf\Dompdf;
use App\Entity\Abonnement;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/user')]
class UserController extends AbstractController
{
    /*
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }
   */
  private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }
    
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    
    public function indexx(UserRepository $userRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        
    
        // récupérer les paramètres de la requête
        $limit = $request->query->getInt('limit', 5);
        $page = $request->query->getInt('page', 1);
        $q = $request->query->get('q');
    
        // construire la requête pour récupérer les utilisateurs
        $queryBuilder = $userRepository->createQueryBuilder('t');
        $queryBuilder->orderBy('t.id', 'ASC');
        if ($q) {
            $queryBuilder->where('t.email LIKE :q')
                         ->setParameter('q', '%'.$q.'%');
        }
        $query = $queryBuilder->getQuery();
    
        // paginer les résultats
        $pagination = $paginator->paginate(
            $query,
            $page,
            $limit
        );
    
        return $this->render('user/indexx.html.twig', [
            'users' => $pagination,
            'q' => $q,
        ]);
    }
    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserRepository $userRepository): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->save($user, true);

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, UserRepository $userRepository): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->save($user, true);

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userRepository->remove($user, true);
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
     
   #[Route('/{userId}/assign-abonnement/{abonnementId}', name: 'app_user_assign',methods: ['GET', 'POST'])]

    public function assignAbonnementToUser(int $userId, int $abonnementId): Response
    {
        // Get the User and Abonnement objects from the database
        $userRepository = $this->managerRegistry->getRepository(User::class);
        $user = $userRepository->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $abonnementRepository = $this->managerRegistry->getRepository(Abonnement::class);
        $abonnement = $abonnementRepository->find($abonnementId);
        if (!$abonnement) {
            throw $this->createNotFoundException('Abonnement not found');
        }
        if($abonnement->getUser()){return new Response('abonnement réservé');}
        if ($user->getAbonnement()) {
      // Get the current DateTime value of the Abonnement object
     
      $currentDate = $user->getAbonnement()->getDuree();
      
    
      // Add one month to the current DateTime value
      $newDate = $currentDate->add(new DateInterval('P1M'));

      // Update the expiration date of the Abonnement object with the new DateTime value
     $abonnement->setDuree($newDate);
     $user->setAbonnement($abonnement);
      var_dump($user) ;
      // Save changes to the database
$entityManager = $this->managerRegistry->getManager();
$entityManager->persist($user);
$entityManager->flush();
  
      // Return a response indicating that the user already has an Abonnement assigned, and that the expiration date has been updated
      return new Response('User already has an Abonnement assigned. Expiration date updated to: '.$newDate->format('Y-m-d H:i:s'));       
     }

        // Set the Abonnement object as the Abonnement property of the User object
        $user->setAbonnement($abonnement);

        // Save changes to the database
        $entityManager = $this->managerRegistry->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        // Return a success response
        return new Response('Abonnement assigned to User.');
    }
    public function assignAbonnementTtoUser(int $userId, int $abonnementId): Response
{
    // Get the User and Abonnement objects from the database
    $userRepository = $this->getDoctrine()->getRepository(User::class);
    $user = $userRepository->find($userId);
    if (!$user) {
        throw $this->createNotFoundException('User not found');
    }

    $abonnementRepository = $this->getDoctrine()->getRepository(Abonnement::class);
    $abonnement = $abonnementRepository->find($abonnementId);
    if (!$abonnement) {
        throw $this->createNotFoundException('Abonnement not found');
    }

    // Check if user already has an Abonnement assigned
    $entityManager = $this->getDoctrine()->getManager();
    $query = $entityManager->createQuery(
        'SELECT COUNT(u.id)
        FROM App\Entity\User u
        WHERE u.id = :userId AND u.abonnement IS NOT NULL'
    )->setParameter('userId', $userId);

    $count = $query->getSingleScalarResult();

    if ($count > 0) {
        // User already has an Abonnement assigned, so update expiration date
        $abonnement = $user->getAbonnement();
        $currentDate = $abonnement->getDuree();
        $newDate = $currentDate->add(new DateInterval('P1M'));
        $abonnement->setDuree($newDate);
    } else {
        // Set the Abonnement object as the Abonnement property of the User object
        $user->setAbonnement($abonnement);
    }

    // Save changes to the database
    $entityManager->persist($user);
    $entityManager->flush();

    // Return a success response
    return new Response('Abonnement assigned to User.');
}
#[Route('/{id}/pdf', name: 'app_user_pdf', methods: ['GET','POST'])]
public function pdf(User $user): Response
{
    // create new PDF document
    $dompdf = new Dompdf();
    
    // generate HTML content for the document
    $html = $this->renderView('user/pdf.html.twig', [
        'user' => $user, 
        
    ]);

    // load HTML into the PDF document
    $dompdf->loadHtml($html);

    // set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');

    // render PDF document
    $dompdf->render();

    // create a response object to return the PDF file
    $response = new Response($dompdf->output());
    
    // set content type to application/pdf
    $response->headers->set('Content-Type', 'application/pdf');

    $disposition = $response->headers->makeDisposition(
        ResponseHeaderBag::DISPOSITION_INLINE,
        'User.pdf'
    );
    $response->headers->set('Content-Disposition', $disposition);

    return $response;
}

}
